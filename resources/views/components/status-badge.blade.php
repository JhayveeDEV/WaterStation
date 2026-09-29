@props(['status'])
@php
    $tone = $status instanceof \App\Enums\OrderStatus ? $status->tone() : 'info';
    $label = $status instanceof \App\Enums\OrderStatus ? $status->label() : $status;
    $classes = [
        'ok'   => 'bg-brand-50 text-brand-700 border-brand-200',
        'info' => 'bg-blue-50 text-blue-700 border-blue-200',
        'warn' => 'bg-amber-50 text-amber-700 border-amber-200',
        'bad'  => 'bg-red-50 text-red-700 border-red-200',
    ][$tone] ?? 'bg-gray-100 text-gray-700 border-gray-200';
@endphp
{{-- data-order-status-badge is used by the live-update JS; keep it --}}
<span {{ $attributes->merge(['class' => "badge badge-dot $classes"]) }} data-order-status-badge>{{ $label }}</span>
