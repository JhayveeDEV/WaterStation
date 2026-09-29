<x-layouts.app title="Add user">
    <div class="max-w-lg">
        <a href="{{ route('admin.users.index') }}" class="mb-3 inline-block text-sm text-gray-500 hover:text-gray-700">&larr; Back to users</a>
        <form method="POST" action="{{ route('admin.users.store') }}" class="card space-y-4 p-6">
            @csrf
            @include('admin.users._form')
            <div class="flex gap-2 pt-2">
                <button class="btn-primary flex-1">Create user</button>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.app>
