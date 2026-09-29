<div class="border-t border-gray-200 px-4 py-3">
    <p class="text-xs text-gray-400">Signed in as <span class="font-medium capitalize text-gray-500">{{ auth()->user()->getRoleNameAttribute() }}</span></p>
    <p class="truncate text-sm text-gray-700" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</p>
</div>
