<x-layouts.app title="Activity log">
    <div class="card divide-y divide-gray-100">
        @forelse ($logs as $log)
            <div class="flex items-start gap-3 p-4">
                <span class="avatar">{{ $log->user ? strtoupper(mb_substr($log->user->name, 0, 1)) : 'S' }}</span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-gray-900">
                        <span class="font-medium">{{ $log->user?->name ?? 'System' }}</span>
                        {{ str_replace('_', ' ', $log->action) }}
                        <span class="badge-muted ml-1">{{ $log->module }}</span>
                    </p>
                    @if ($log->description)
                        <p class="mt-0.5 text-xs text-gray-500">{{ $log->description }}</p>
                    @endif
                </div>
                <time class="shrink-0 text-xs text-gray-400" datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->format('M j, Y g:i A') }}">
                    {{ $log->created_at->diffForHumans(short: true) }}
                </time>
            </div>
        @empty
            <x-empty-state title="No activity recorded yet" />
        @endforelse
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-layouts.app>
