@extends('layouts.admin')

@section('title', 'Edit Role — ' . $role->display_name)

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-violet-500 mb-1">Roles & Permissions</p>
            <h1 class="text-3xl font-black text-gray-900">{{ $role->display_name }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                <code class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded">{{ $role->name }}</code>
                &mdash; {{ $roleUsersTotal }} user{{ $roleUsersTotal !== 1 ? 's' : '' }} currently hold this role
            </p>
        </div>
        <a href="{{ route('admin.roles.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-sm transition-all">
            ← All Roles
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-5 py-4 text-sm font-semibold">
        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Validation Errors --}}
    @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-4 text-sm">
        <p class="font-bold mb-1">Please fix the following errors:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="space-y-6">

        {{-- Users holding this role (read-only context panel) --}}
        @if($roleUsersTotal > 0)
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-xs font-black uppercase tracking-widest text-gray-500">Users With This Role</h2>
                <span class="text-xs text-gray-400">{{ $roleUsersTotal }} total{{ $roleUsersTotal > 10 ? ' — showing first 10' : '' }}</span>
            </div>
            <div class="px-6 py-4">
                <div class="flex flex-wrap gap-2">
                    @foreach($roleUsers as $u)
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 border border-gray-100 rounded-full">
                        <div class="h-5 w-5 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-[10px] font-black uppercase">
                            {{ substr($u->name, 0, 1) }}
                        </div>
                        <span class="text-xs font-medium text-gray-700">{{ $u->name }}</span>
                    </div>
                    @endforeach
                    @if($roleUsersTotal > 10)
                    <div class="flex items-center px-3 py-1.5 bg-gray-50 border border-gray-100 rounded-full">
                        <span class="text-xs font-medium text-gray-400">+{{ $roleUsersTotal - 10 }} more</span>
                    </div>
                    @endif
                </div>
                <p class="text-[11px] text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mt-3">
                    ⚠ Changing permissions on this role will immediately affect all {{ $roleUsersTotal }} user{{ $roleUsersTotal !== 1 ? 's' : '' }} who hold it. Their permission cache will be cleared automatically.
                </p>
            </div>
        </div>
        @endif

        {{-- Edit Form --}}
        <form method="POST" action="{{ route('admin.roles.update', $role) }}">
            @csrf
            @method('PATCH')

            {{-- Role Details --}}
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden mb-6">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gray-500">Role Details</h2>
                </div>
                <div class="p-6 space-y-5">
                    {{-- Name is read-only — changing slugs breaks existing code references --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Internal Name</label>
                        <div class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono text-gray-500">
                            {{ $role->name }}
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1.5">Internal name cannot be changed after creation.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="display_name" class="block text-sm font-bold text-gray-700 mb-1.5">
                                Display Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="display_name" name="display_name"
                                   value="{{ old('display_name', $role->display_name) }}"
                                   class="w-full px-4 py-2.5 border {{ $errors->has('display_name') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
                            @error('display_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-bold text-gray-700 mb-1.5">Description</label>
                            <textarea id="description" name="description" rows="2"
                                      class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all resize-none">{{ old('description', $role->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Permission Assignment --}}
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden mb-6">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-xs font-black uppercase tracking-widest text-gray-500">Permissions</h2>
                        <p class="text-[11px] text-gray-400 mt-0.5">
                            <span id="selected-count">{{ count($assignedPermissionIds) }}</span> selected
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="select-all"
                                class="text-xs font-bold text-violet-600 hover:text-violet-800 transition-colors px-2 py-1 hover:bg-violet-50 rounded-lg">
                            Select All
                        </button>
                        <span class="text-gray-200">|</span>
                        <button type="button" id="deselect-all"
                                class="text-xs font-bold text-gray-500 hover:text-gray-800 transition-colors px-2 py-1 hover:bg-gray-50 rounded-lg">
                            Deselect All
                        </button>
                    </div>
                </div>

                <div class="divide-y divide-gray-50">
                    @foreach($permissions as $group => $perms)
                    <div class="px-6 py-5">
                        <div class="flex items-center gap-3 mb-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">{{ ucfirst($group) }}</p>
                            <div class="flex-1 h-px bg-gray-100"></div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($perms as $perm)
                            @php $checked = in_array($perm->id, old('permissions', $assignedPermissionIds)); @endphp
                            <label for="perm_{{ $perm->id }}"
                                   class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all group
                                          {{ $checked ? 'border-violet-200 bg-violet-50' : 'border-gray-100 bg-gray-50/50 hover:border-violet-200 hover:bg-violet-50/30' }}">
                                <input type="checkbox"
                                       id="perm_{{ $perm->id }}"
                                       name="permissions[]"
                                       value="{{ $perm->id }}"
                                       {{ $checked ? 'checked' : '' }}
                                       class="permission-checkbox mt-0.5 h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                                <div>
                                    <p class="text-sm font-bold text-gray-800 group-hover:text-violet-700 transition-colors">{{ $perm->display_name }}</p>
                                    <code class="text-[10px] font-mono text-gray-400">{{ $perm->name }}</code>
                                    @if($perm->description)
                                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $perm->description }}</p>
                                    @endif
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Audit Notice --}}
            <div class="flex items-center gap-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-6">
                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                All role changes are audit-logged to the security log with your admin account, timestamp, and the exact permissions added/removed.
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                        class="px-6 py-3 bg-violet-600 hover:bg-violet-700 text-white rounded-xl font-bold text-sm transition-all shadow-lg shadow-violet-200">
                    Save Changes
                </button>
                <a href="{{ route('admin.roles.index') }}"
                   class="px-6 py-3 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 rounded-xl font-bold text-sm transition-all">
                    Cancel
                </a>
            </div>
        </form>
    </div>

</div>

<script>
const checkboxes = document.querySelectorAll('.permission-checkbox');
const countEl = document.getElementById('selected-count');

function updateCount() {
    countEl.textContent = document.querySelectorAll('.permission-checkbox:checked').length;
}

checkboxes.forEach(cb => cb.addEventListener('change', updateCount));

document.getElementById('select-all').addEventListener('click', () => {
    checkboxes.forEach(cb => cb.checked = true);
    updateCount();
});
document.getElementById('deselect-all').addEventListener('click', () => {
    checkboxes.forEach(cb => cb.checked = false);
    updateCount();
});
</script>
@endsection
