<x-layouts.app title="Roles &amp; permissions">
    <p class="page-note">
        Every user has exactly one role, assigned under <a href="{{ route('admin.users.index') }}" class="text-brand-600 hover:underline">Users</a>.
        Use this screen to choose what each role can do. The <span class="font-medium text-gray-700">admin</span> role
        always keeps every permission, so there's always a way back into this screen.
        A permission only affects routes and actions that check it; it does not create pages or menu items.
    </p>

    <div class="space-y-6">
        @foreach ($roles as $role)
            <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="card">
                @csrf @method('PUT')
                <div class="card-header">
                    <div class="flex items-center gap-2">
                        <h3 class="card-title capitalize">{{ $role->name }}</h3>
                        <span class="badge-muted">{{ $role->permissions->count() }} of {{ $permissions->count() }}</span>
                    </div>
                    @if ($role->name !== 'admin')
                        <button class="btn-primary btn-sm">Save changes</button>
                    @else
                        <span class="badge-muted">Always has all permissions</span>
                    @endif
                </div>

                <div class="grid gap-2 p-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($permissions as $permission)
                        @php $checked = $role->permissions->contains('id', $permission->id); @endphp
                        <label class="check-tile {{ $role->name === 'admin' ? 'opacity-70' : 'cursor-pointer hover:bg-gray-50' }}">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                   {{ $checked ? 'checked' : '' }} {{ $role->name === 'admin' ? 'disabled' : '' }}
                                   class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span>{{ ucfirst($permission->name) }}</span>
                        </label>
                    @endforeach
                </div>
            </form>
        @endforeach
    </div>
</x-layouts.app>
