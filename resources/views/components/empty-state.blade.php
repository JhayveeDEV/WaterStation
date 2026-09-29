@props(['title' => 'Nothing here yet', 'description' => null, 'card' => false])
<div {{ $attributes->class(['flex flex-col items-center justify-center px-6 py-14 text-center', 'card' => $card]) }}>
    <span class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-400" aria-hidden="true">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8zM3 8l9 5 9-5"/></svg>
    </span>
    <p class="text-sm font-medium text-gray-900">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-gray-500">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
