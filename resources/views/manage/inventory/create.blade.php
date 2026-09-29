<x-layouts.app title="Add product">
    <div class="max-w-lg">
        <a href="{{ route('manage.inventory.index') }}" class="mb-3 inline-block text-sm text-gray-500 hover:text-gray-700">&larr; Back to inventory</a>
        <form method="POST" action="{{ route('manage.inventory.store') }}" enctype="multipart/form-data" class="card space-y-4 p-6">
            @csrf
            @include('manage.inventory._form')
            <div class="flex gap-2 pt-2">
                <button class="btn-primary flex-1">Save product</button>
                <a href="{{ route('manage.inventory.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.app>
