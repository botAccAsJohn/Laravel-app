<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DispatchProductImportBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProductImportController extends Controller
{
    /**
     * Show the import CSV form.
     */
    public function showForm()
    {
        Gate::authorize('import_products');
        return view('admin.import.form');
    }

    /**
     * Handle the CSV upload.
     * Stores the file, dispatches an orchestrator job, and shows a "queued" page.
     */
    public function import(Request $request)
    {
        Gate::authorize('import_products');

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:20480', // 20MB max
        ]);

        try {
            $file = $request->file('csv_file');
            $fileName = 'import_' . time() . '_' . Str::random(6) . '.csv';
            $filePath = $file->storeAs('temp', $fileName);

            // Unique key the orchestrator job writes the real batch ID into
            $batchCacheKey = 'import_batch_' . Str::uuid();

            // Dispatch the orchestrator — the HTTP request returns immediately
            DispatchProductImportBatch::dispatch($filePath, $batchCacheKey);

            // Show a polling page that waits for the orchestrator to write the batch ID
            return view('admin.import.queued', compact('batchCacheKey'));

        } catch (Throwable $e) {
            return back()->withErrors(['csv_file' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Polling endpoint: returns the batch ID once the orchestrator has dispatched it.
     * The "queued" page polls this until a batch_id is available, then redirects.
     */
    public function pollBatchId(string $batchCacheKey)
    {
        Gate::authorize('import_products');

        $data = Cache::get($batchCacheKey);

        if (!$data) {
            return response()->json(['status' => 'pending']);
        }

        if (isset($data['error'])) {
            return response()->json(['status' => 'error', 'message' => $data['error']]);
        }

        return response()->json([
            'status'   => 'ready',
            'batch_id' => $data['batch_id'],
        ]);
    }

    /**
     * Show the real-time import progress page.
     */
    public function progress(string $batchId)
    {
        Gate::authorize('import_products');

        $batch = Bus::findBatch($batchId);

        if (!$batch) {
            abort(404, 'Job batch not found.');
        }

        return view('admin.import.progress', compact('batch'));
    }

    /**
     * JSON polling endpoint for the progress page.
     */
    public function getProgress(string $batchId)
    {
        Gate::authorize('import_products');

        $batch = Bus::findBatch($batchId);

        if (!$batch) {
            return response()->json(['error' => 'Batch not found'], 404);
        }

        $processed = $batch->totalJobs - $batch->pendingJobs - $batch->failedJobs;

        return response()->json([
            'id'             => $batch->id,
            'name'           => $batch->name,
            'total_jobs'     => $batch->totalJobs,
            'pending_jobs'   => $batch->pendingJobs,
            'processed_jobs' => max(0, $processed),
            'failed_jobs'    => $batch->failedJobs,
            'progress'       => $batch->progress(),
            'finished'       => $batch->finished(),
            'cancelled'      => $batch->cancelled(),
            'failed'         => $batch->hasFailures(),
        ]);
    }

    /**
     * Cancel an in-progress batch.
     */
    public function cancel(string $batchId)
    {
        Gate::authorize('import_products');

        $batch = Bus::findBatch($batchId);

        if (!$batch) {
            abort(404, 'Job batch not found.');
        }

        $batch->cancel();

        return back()->with('status', 'Batch import has been cancelled.');
    }
}
