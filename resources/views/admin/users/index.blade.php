<x-layouts.app title="Users">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap gap-2">
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M21 21l-4.3-4.3M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/></svg>
                <input name="search" value="{{ request('search') }}" placeholder="Search name or email" class="input w-64 pl-9" aria-label="Search users">
            </div>
            <select name="role" class="input w-auto" aria-label="Filter by role">
                <option value="">All roles</option>
                @foreach (['admin', 'staff', 'customer', 'rider'] as $r)
                    <option value="{{ $r }}" {{ request('role') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary">Filter</button>
            @if (request()->hasAny(['search', 'role']))
                <a href="{{ route('admin.users.index') }}" class="btn-ghost">Clear</a>
            @endif
        </form>
        <a href="{{ route('admin.users.create') }}" class="btn-primary">Add user</a>
    </div>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="avatar">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-gray-900">{{ $user->name }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="capitalize text-gray-700">{{ $user->roles->first()?->name ?? '—' }}</td>
                        <td>
                            <span class="{{ $user->status === 'active' ? 'badge-success' : 'badge-muted' }} badge-dot">{{ ucfirst($user->status) }}</span>
                        </td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn-ghost btn-sm">Edit</a>
                            @if ($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button class="btn-ghost btn-sm {{ $user->status === 'active' ? 'text-red-600 hover:bg-red-50' : '' }}">
                                        {{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-gray-400">No users match your search.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.app>
