@php
    $unread = auth()->user()->unreadNotificationCount();
    $recent = auth()->user()->appNotifications()->latest()->limit(6)->get();
@endphp
<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button @click="open = !open" :aria-expanded="open" aria-haspopup="true" aria-label="Notifications"
            class="relative flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0m6 0H9"/>
        </svg>
        {{-- id / data-count are read by the live-update JS; keep them --}}
        <span id="notif-badge" data-count="{{ $unread }}"
              class="{{ $unread ? '' : 'hidden' }} absolute -right-0.5 -top-0.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-medium text-white ring-2 ring-white">
            {{ $unread > 9 ? '9+' : $unread }}
        </span>
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.100ms @click.outside="open = false"
         class="fixed inset-x-4 top-16 z-50 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-[22rem]">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <p class="text-sm font-semibold text-gray-900">Notifications</p>
            <a href="{{ route('notifications.index') }}" class="text-xs font-medium text-brand-600 hover:underline">View all</a>
        </div>
        <div id="notif-list" class="max-h-96 overflow-y-auto">
            @forelse ($recent as $n)
                <a href="{{ $n->url ?? '#' }}" data-notification-id="{{ $n->id }}" data-read="{{ $n->is_read ? '1' : '0' }}"
                   class="flex gap-3 border-b border-gray-100 px-4 py-3 last:border-0 hover:bg-gray-50 {{ $n->is_read ? '' : 'bg-brand-50/40' }}">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $n->is_read ? 'bg-transparent' : 'bg-brand-600' }}"></span>
                    <span class="min-w-0">
                        <span class="block text-sm font-medium text-gray-900">{{ $n->title }}</span>
                        <span class="mt-0.5 block text-xs leading-snug text-gray-500">{{ $n->message }}</span>
                        <span class="mt-1 block text-xs text-gray-400">{{ $n->created_at->diffForHumans() }}</span>
                    </span>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-gray-400">You're all caught up.</p>
            @endforelse
        </div>
    </div>
</div>
