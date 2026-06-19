@extends('layouts.app')

@push('styles')
@vite(['resources/css/app.css', 'resources/js/app.js'])
@endpush

@section('title', 'Import Progress — Admin')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            
            {{-- HEADER --}}
            <div class="flex flex-col gap-4 border-b border-gray-200 px-8 py-6 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-900">
                        <svg id="status-icon-running" class="h-6 w-6 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg id="status-icon-finished" class="h-6 w-6 text-white hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg id="status-icon-cancelled" class="h-6 w-6 text-white hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Import Progress</h1>
                        <p class="text-sm font-mono text-gray-500">Batch ID: <span id="batch-id-text">{{ $batch->id }}</span></p>
                    </div>
                </div>

                <div>
                    <span id="badge-status" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold border bg-yellow-50 text-yellow-700 border-yellow-200">
                        <span class="h-2 w-2 rounded-full bg-yellow-500 animate-pulse" id="badge-dot"></span>
                        <span id="badge-text">Processing</span>
                    </span>
                </div>
            </div>

            <div class="px-8 py-8 space-y-8">
                {{-- STATUS MESSAGE --}}
                @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('status') }}
                </div>
                @endif

                {{-- PROGRESS BAR & PERCENTAGE --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm font-semibold text-gray-700">
                        <span>Overall Progress</span>
                        <span id="progress-percentage-text" class="text-lg font-bold text-indigo-600">0%</span>
                    </div>
                    <div class="h-4 w-full rounded-full bg-gray-100 overflow-hidden border border-gray-200" style="height: 16px; background-color: #f3f4f6; border-radius: 9999px; overflow: hidden; border: 1px solid #e5e7eb;">
                        <div id="progress-bar-fill" class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-600 transition-all duration-500 ease-out" style="width: 0%; height: 100%; background: linear-gradient(to right, #6366f1, #7c3aed); border-radius: 9999px;"></div>
                    </div>
                </div>

                {{-- STATS CARDS --}}
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="text-sm font-semibold text-gray-500">Total Rows</div>
                        <div class="mt-2 text-3xl font-extrabold text-gray-900" id="stat-total">0</div>
                    </div>

                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/30 p-5 shadow-sm">
                        <div class="text-sm font-semibold text-emerald-600">Processed</div>
                        <div class="mt-2 text-3xl font-extrabold text-emerald-700" id="stat-processed">0</div>
                    </div>

                    <div class="rounded-xl border border-rose-200 bg-rose-50/30 p-5 shadow-sm">
                        <div class="text-sm font-semibold text-rose-600">Failed</div>
                        <div class="mt-2 text-3xl font-extrabold text-rose-700" id="stat-failed">0</div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="text-sm font-semibold text-gray-500">Pending Jobs</div>
                        <div class="mt-2 text-3xl font-extrabold text-gray-900" id="stat-pending">0</div>
                    </div>
                </div>

                {{-- ACTIONS --}}
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <a href="{{ route('admin.import.form') }}" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Import Another File
                    </a>

                    <form id="cancel-form" action="{{ route('admin.import.cancel', $batch->id) }}" method="POST">
                        @csrf
                        <button type="submit" id="cancel-btn" class="rounded-lg bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            Cancel Import
                        </button>
                    </form>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const batchId = "{{ $batch->id }}";
        const statusUrl = "{{ route('admin.import.progress.status', ['batchId' => ':batchId']) }}".replace(':batchId', batchId);
        
        const progressBarFill = document.getElementById('progress-bar-fill');
        const progressPercentageText = document.getElementById('progress-percentage-text');
        
        const statTotal = document.getElementById('stat-total');
        const statProcessed = document.getElementById('stat-processed');
        const statFailed = document.getElementById('stat-failed');
        const statPending = document.getElementById('stat-pending');
        
        const badgeStatus = document.getElementById('badge-status');
        const badgeDot = document.getElementById('badge-dot');
        const badgeText = document.getElementById('badge-text');
        
        const statusIconRunning = document.getElementById('status-icon-running');
        const statusIconFinished = document.getElementById('status-icon-finished');
        const statusIconCancelled = document.getElementById('status-icon-cancelled');
        
        const cancelBtn = document.getElementById('cancel-btn');

        let intervalId;

        function updateProgress() {
            fetch(statusUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Update stats
                    statTotal.textContent = data.total_jobs;
                    statProcessed.textContent = data.processed_jobs;
                    statFailed.textContent = data.failed_jobs;
                    statPending.textContent = data.pending_jobs;
                    
                    // Update progress bar
                    progressBarFill.style.width = data.progress + '%';
                    progressPercentageText.textContent = data.progress + '%';

                    // Update UI state based on batch status
                    if (data.finished || data.cancelled) {
                        clearInterval(intervalId);
                        cancelBtn.disabled = true;
                        
                        statusIconRunning.classList.add('hidden');
                        
                        if (data.cancelled) {
                            badgeStatus.className = "inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold border bg-rose-50 text-rose-700 border-rose-200";
                            badgeDot.className = "h-2 w-2 rounded-full bg-rose-500";
                            badgeText.textContent = "Cancelled";
                            statusIconCancelled.classList.remove('hidden');
                        } else if (data.failed) {
                            badgeStatus.className = "inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold border bg-rose-50 text-rose-700 border-rose-200";
                            badgeDot.className = "h-2 w-2 rounded-full bg-rose-500";
                            badgeText.textContent = "Failed";
                            statusIconCancelled.classList.remove('hidden');
                        } else {
                            badgeStatus.className = "inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200";
                            badgeDot.className = "h-2 w-2 rounded-full bg-emerald-500";
                            badgeText.textContent = "Finished";
                            statusIconFinished.classList.remove('hidden');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching progress:', error);
                });
        }

        // Poll immediately and then every 1.5 seconds
        updateProgress();
        intervalId = setInterval(updateProgress, 1500);
    });
</script>
@endsection
