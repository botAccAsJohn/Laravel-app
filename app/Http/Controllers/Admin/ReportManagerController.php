<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReportManagerController extends Controller
{
    public function index()
    {
        $disk = Storage::disk('reports');
        $files = $disk->files();

        $reportFiles = [];

        foreach ($files as $file) {
            $reportFiles[] = [
                'name' => $file,
                'size' => $disk->size($file),
                'last_modified' => Carbon::createFromTimestamp($disk->lastModified($file)),
            ];
        }

        return view('admin.reports.index', compact('reportFiles'));
    }

    public function archive(Request $request)
    {
        $file = $request->input('file');
        $disk = Storage::disk('reports');

        if (!$disk->exists($file)) {
            return redirect()->back()->with('error', 'File not found or already archived/deleted.');
        }

        $archivePath = 'archive/' . basename($file);
        
        $disk->copy($file, $archivePath);
        $disk->delete($file);

        return redirect()->back()->with('success', "File {$file} archived successfully.");
    }

    public function bulkCleanup()
    {
        $disk = Storage::disk('reports');
        $files = $disk->files();

        $threshold = now()->subDays(30)->timestamp;
        $deletedCount = 0;

        foreach ($files as $file) {
            if ($disk->exists($file) && $disk->lastModified($file) < $threshold) {
                $disk->delete($file);
                $deletedCount++;
            }
        }

        return redirect()->back()->with('success', "Bulk cleanup completed. {$deletedCount} old files deleted.");
    }
}
