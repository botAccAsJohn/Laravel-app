@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
        <h1 class="text-3xl font-bold text-gray-800">
            System Logs
        </h1>
        
        <form method="GET" action="{{ route('logs.index') }}" class="flex items-center space-x-3 bg-white p-2 rounded-lg shadow-sm border border-gray-100">
            <label for="type" class="text-gray-700 font-medium whitespace-nowrap">View Log:</label>
            <select name="type" id="type" onchange="this.form.submit()" class="form-select block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="products" {{ $logType === 'products' ? 'selected' : '' }}>Products</option>
                <option value="db" {{ $logType === 'db' ? 'selected' : '' }}>Database (DB)</option>
                <option value="orders" {{ $logType === 'orders' ? 'selected' : '' }}>Orders</option>
            </select>
        </form>
    </div>

    <div class="bg-gray-900 rounded-xl p-6 overflow-x-auto shadow-lg border border-gray-800">
        <div class="flex items-center mb-4 space-x-2">
            <div class="w-3 h-3 rounded-full bg-red-500"></div>
            <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
            <div class="w-3 h-3 rounded-full bg-green-500"></div>
            <span class="text-gray-400 text-xs font-mono ml-4">storage/logs/{{ $logType }}/{{ $logType }}-{{ date('Y-m-d') }}.log</span>
        </div>
        <pre class="text-green-400 font-mono text-sm whitespace-pre-wrap">
@if(empty($logs))
<span class="text-gray-500">No logs found for {{ $logType }} today.</span>
@else
@foreach($logs as $log)
{{ $log }}@endforeach
@endif
        </pre>
    </div>
</div>
@endsection
