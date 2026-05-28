@extends('layouts.admin')

@section('title', 'Edit Roles — ' . $user->name)

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-indigo-500 mb-1">Role Assignment</p>
            <h1 class="text-3xl font-black text-gray-900">{{ $user->name }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $user->email }}</p>
        </div>
        <a href="{{ route('admin.users.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-sm transition-all">
            ← All Users
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

    {{-- Role assignment form --}}
    <form method="POST" action="{{ route('admin.users.update-roles', $user) }}" id="role-form">
        @csrf
        @method('PATCH')

        <div class="space-y-4 mb-8">
            @foreach($roles->groupBy(fn($r) => $r->name === 'customer' ? 'Standard' : 'Staff') as $group => $groupRoles)
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gray-500">{{ $group }} Roles</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach($groupRoles as $role)
                    @php $isAssigned = in_array($role->id, $userRoleIds); @endphp
                    <label for="role_{{ $role->id }}"
                           class="flex items-start gap-4 px-6 py-5 cursor-pointer hover:bg-indigo-50/30 transition-colors group">
                        <input type="checkbox"
                               id="role_{{ $role->id }}"
                               name="roles[]"
                               value="{{ $role->id }}"
                               {{ $isAssigned ? 'checked' : '' }}
                               class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-bold text-gray-900 group-hover:text-indigo-700 transition-colors">
                                    {{ $role->display_name }}
                                </span>
                                <code class="text-[10px] px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded font-mono">{{ $role->name }}</code>
                                @if($isAssigned)
                                <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Assigned</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500">{{ $role->description }}</p>

                            {{-- Permission pills for this role --}}
                            @if($role->permissions->isNotEmpty())
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach($role->permissions->sortBy('group') as $perm)
                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 font-mono">{{ $perm->name }}</span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        {{-- Audit note --}}
        <div class="flex items-center gap-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-6">
            <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            All role changes are audit-logged to the security log with your admin account, timestamp, and the exact roles added/removed.
        </div>

        <div class="flex items-center gap-4">
            <button type="submit"
                    class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm transition-all shadow-lg hover:shadow-indigo-200">
                Save Role Changes
            </button>
            <a href="{{ route('admin.users.index') }}"
               class="px-6 py-3 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 rounded-xl font-bold text-sm transition-all">
                Cancel
            </a>
        </div>
    </form>

    {{-- Current permissions summary --}}
    <div class="mt-10 bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="font-bold text-gray-900">Effective Permissions</h2>
            <p class="text-xs text-gray-400 mt-0.5">All permissions granted via currently assigned roles</p>
        </div>
        <div class="p-6">
            @php
                $effectivePerms = $user->roles->load('permissions')
                    ->flatMap(fn($r) => $r->permissions)
                    ->unique('id')
                    ->sortBy('group');
            @endphp
            @if($effectivePerms->isEmpty())
                <p class="text-sm text-gray-400 italic">No permissions — assign a role above.</p>
            @else
                @foreach($effectivePerms->groupBy('group') as $group => $perms)
                <div class="mb-4">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">{{ ucfirst($group) }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($perms as $perm)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-semibold">
                            <svg class="w-3 h-3 text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ $perm->display_name }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection
