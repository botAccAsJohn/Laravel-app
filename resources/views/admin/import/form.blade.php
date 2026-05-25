@extends('layouts.app')

@push('styles')
@vite(['resources/css/app.css', 'resources/js/app.js'])
@endpush

@section('title', 'Import Products — Admin')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            
            {{-- HEADER --}}
            <div class="flex items-center gap-4 border-b border-gray-200 px-8 py-6">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-900">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="17 8 12 3 7 8" />
                        <line x1="12" y1="3" x2="12" y2="15" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Batch Product Import</h1>
                    <p class="text-sm font-medium uppercase tracking-widest text-gray-500">Import CSV via Job Batching</p>
                </div>
            </div>

            <div class="px-8 py-8">
                {{-- FLASH MESSAGES --}}
                @if (session('status'))
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('status') }}
                </div>
                @endif

                @if ($errors->any())
                <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- FORM --}}
                <form action="{{ route('admin.import.process') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                    @csrf
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Upload CSV File</label>
                        <div class="flex justify-center rounded-xl border-2 border-dashed border-gray-300 px-6 py-10 hover:border-gray-400 transition-colors">
                            <div class="text-center space-y-2">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="csv_file" class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 hover:text-indigo-500">
                                        <span>Select a file</span>
                                        <input id="csv_file" name="csv_file" type="file" accept=".csv" class="sr-only" required>
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500" id="file-name-label">CSV files up to 10MB</p>
                            </div>
                        </div>
                    </div>

                    {{-- CSV STRUCTURE INFO --}}
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-6">
                        <h3 class="text-md font-semibold text-gray-900 mb-3 flex items-center gap-2">
                            <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Expected CSV Structure
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">
                            The CSV file must contain a header row. Below are the supported columns:
                        </p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs text-left text-gray-500 border border-gray-200">
                                <thead class="bg-gray-100 uppercase text-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 border-b">Column</th>
                                        <th class="px-4 py-2 border-b">Type</th>
                                        <th class="px-4 py-2 border-b">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr>
                                        <td class="px-4 py-2 font-mono font-bold text-gray-900">name</td>
                                        <td class="px-4 py-2">String (Required)</td>
                                        <td class="px-4 py-2">The name of the product.</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-gray-900">slug</td>
                                        <td class="px-4 py-2">String (Optional)</td>
                                        <td class="px-4 py-2">Unique URL-friendly identifier. Auto-generated if omitted.</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-gray-900">price</td>
                                        <td class="px-4 py-2">Numeric (Required)</td>
                                        <td class="px-4 py-2">Unit price of the product (e.g., 45.40).</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-gray-900">discount_price</td>
                                        <td class="px-4 py-2">Numeric (Optional)</td>
                                        <td class="px-4 py-2">Discounted price if applicable.</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-gray-900">description</td>
                                        <td class="px-4 py-2">String (Optional)</td>
                                        <td class="px-4 py-2">Detailed description of the product.</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-gray-900">category_id</td>
                                        <td class="px-4 py-2">Integer (Optional)</td>
                                        <td class="px-4 py-2">Database ID of the associated Category.</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-gray-900">tags</td>
                                        <td class="px-4 py-2">JSON / String (Optional)</td>
                                        <td class="px-4 py-2">JSON array or comma-separated tags (e.g., "test, new, hot").</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-gray-900">quantity</td>
                                        <td class="px-4 py-2">Integer (Optional)</td>
                                        <td class="px-4 py-2">Stock quantity. Defaults to 0.</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-gray-900">is_active</td>
                                        <td class="px-4 py-2">Boolean (Optional)</td>
                                        <td class="px-4 py-2">Whether the product is active (true/false, 1/0). Defaults to true.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- ACTIONS --}}
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('dashboard') }}" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button type="submit" class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 transition shadow-sm">
                            Start Import Batch
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
    document.getElementById('csv_file').addEventListener('change', function(e) {
        const fileName = e.target.files[0] ? e.target.files[0].name : "Select a file";
        document.getElementById('file-name-label').textContent = fileName;
        document.getElementById('file-name-label').classList.remove('text-gray-500');
        document.getElementById('file-name-label').classList.add('text-indigo-600', 'font-semibold');
    });
</script>
@endsection
