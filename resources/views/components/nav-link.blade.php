@props(['href', 'active' => false, 'icon' => null])
@php
    $icons = [
        'dashboard' => 'M4 4h7v7H4zM13 4h7v4h-7zM13 10h7v10h-7zM4 13h7v7H4z',
        'board'     => 'M4 5h4v14H4zM10 5h4v9h-4zM16 5h4v6h-4z',
        'history'   => 'M12 21a9 9 0 100-18 9 9 0 000 18zM12 7v5l3 2',
        'inventory' => 'M21 8l-9-5-9 5v8l9 5 9-5V8zM3 8l9 5 9-5M12 13v8',
        'users'     => 'M16 19v-1a4 4 0 00-4-4H8a4 4 0 00-4 4v1M10 11a3 3 0 100-6 3 3 0 000 6zM20 19v-1a3 3 0 00-2-2.8M16 5.2a3 3 0 010 5.6',
        'roles'     => 'M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6l8-3z',
        'refunds'   => 'M9 14L4 9l5-5M4 9h10a6 6 0 010 12h-3',
        'pin'       => 'M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11zM12 12a2 2 0 100-4 2 2 0 000 4z',
        'reports'   => 'M5 20V11M12 20V5M19 20v-8',
        'activity'  => 'M4 6h16M4 12h16M4 18h10',
        'shop'      => 'M4 7h16l-1 4H5L4 7zM6 11v9h12v-9M10 20v-5h4v5',
        'cart'      => 'M3 4h2l2.4 11h9.2L19 8H6M9 20a1 1 0 100-2 1 1 0 000 2zM17 20a1 1 0 100-2 1 1 0 000 2z',
        'orders'    => 'M6 3h12v18l-3-2-3 2-3-2-3 2V3zM9 8h6M9 12h6',
        'truck'     => 'M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 100-4 2 2 0 000 4zM17 19a2 2 0 100-4 2 2 0 000 4z',
    ];
@endphp
<a href="{{ $href }}" {{ $attributes->merge(['class' => $active ? 'nav-link-active' : 'nav-link']) }} @if ($active) aria-current="page" @endif>
    @if ($icon && isset($icons[$icon]))
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icons[$icon] }}"/></svg>
    @endif
    {{ $slot }}
</a>
