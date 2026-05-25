@extends('layouts.app')

@push('styles')
@vite(['resources/css/app.css', 'resources/js/app.js'])
@endpush

@section('title', 'Preparing Import — Admin')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm p-10 text-center">

            {{-- Spinner --}}
            <div class="flex justify-center mb-6">
                <div style="width:64px;height:64px;border:4px solid #e5e7eb;border-top-color:#6366f1;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
            </div>

            <h1 class="text-xl font-bold text-gray-900 mb-2">Preparing Your Import</h1>
            <p class="text-sm text-gray-500 mb-6">
                Your CSV is being parsed and the batch is being queued.<br>
                You will be redirected automatically…
            </p>

            <div id="status-msg" class="text-xs font-semibold text-gray-400">Waiting for queue worker…</div>

            {{-- Error box (hidden by default) --}}
            <div id="error-box" class="hidden mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700"></div>

        </div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const pollUrl  = "{{ route('admin.import.poll', ['batchCacheKey' => $batchCacheKey]) }}";
    const progressBase = "{{ url('/admin/import/progress') }}";
    const statusMsg = document.getElementById('status-msg');
    const errorBox  = document.getElementById('error-box');
    let attempts = 0;
    const MAX_ATTEMPTS = 120; // 120 × 1.5s = 3 minutes max

    function poll() {
        attempts++;
        statusMsg.textContent = 'Checking batch status… (attempt ' + attempts + ')';

        fetch(pollUrl)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'ready') {
                    statusMsg.textContent = 'Batch ready! Redirecting…';
                    window.location.href = progressBase + '/' + data.batch_id;

                } else if (data.status === 'error') {
                    errorBox.textContent = data.message || 'An unknown error occurred.';
                    errorBox.classList.remove('hidden');
                    statusMsg.textContent = 'Import failed.';

                } else if (attempts < MAX_ATTEMPTS) {
                    setTimeout(poll, 1500);

                } else {
                    statusMsg.textContent = 'Timed out waiting. Check queue worker is running.';
                }
            })
            .catch(() => {
                if (attempts < MAX_ATTEMPTS) {
                    setTimeout(poll, 2000);
                }
            });
    }

    setTimeout(poll, 1500); // first poll after 1.5s
});
</script>
@endsection
