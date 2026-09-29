<x-layouts.app title="Notifications">
    @php $unread = auth()->user()->unreadNotificationCount(); @endphp
    <div class="mx-auto max-w-2xl">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-gray-900">All notifications</h3>
                @if ($unread)
                    <span class="badge-success num">{{ $unread }} unread</span>
                @endif
            </div>
            @if ($unread)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="btn-secondary btn-sm">Mark all as read</button>
                </form>
            @endif
        </div>

        <div class="card divide-y divide-gray-100">
            @forelse ($notifications as $n)
                <a href="{{ $n->url ?? '#' }}" data-notification-id="{{ $n->id }}" data-read="{{ $n->is_read ? '1' : '0' }}"
                   class="flex items-start gap-3 px-4 py-3.5 transition-colors hover:bg-gray-50 {{ $n->is_read ? '' : 'bg-brand-50/40' }}">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $n->is_read ? 'bg-gray-200' : 'bg-brand-500' }}" aria-hidden="true"></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-sm {{ $n->is_read ? 'font-medium text-gray-700' : 'font-semibold text-gray-900' }}">{{ $n->title }}</p>
                            <time class="shrink-0 text-xs text-gray-400" datetime="{{ $n->created_at->toIso8601String() }}" title="{{ $n->created_at->format('M j, Y g:i A') }}">
                                {{ $n->created_at->diffForHumans() }}
                            </time>
                        </div>
                        <p class="mt-0.5 text-sm leading-snug text-gray-500">{{ $n->message }}</p>
                    </div>
                </a>
            @empty
                <x-empty-state title="No notifications" description="You're all caught up." />
            @endforelse
        </div>

        <div class="mt-4">{{ $notifications->links() }}</div>
    </div>
</x-layouts.app>