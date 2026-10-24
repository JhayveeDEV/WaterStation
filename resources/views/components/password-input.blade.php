@props([
    'id',
    'name',
    'autocomplete' => 'new-password',
    'required' => false,
    'invalid' => false,
])

<div x-data="{ passwordVisible: false }">
    <div class="relative">
        <input
            id="{{ $id }}"
            :type="passwordVisible ? 'text' : 'password'"
            name="{{ $name }}"
            autocomplete="{{ $autocomplete }}"
            @if ($required) required @endif
            {{ $attributes->merge(['class' => 'input pr-11' . ($invalid ? ' input-error' : '')]) }}
        >
        <button
            type="button"
            @click="passwordVisible = !passwordVisible"
            :aria-label="passwordVisible ? 'Hide password' : 'Show password'"
            :title="passwordVisible ? 'Hide password' : 'Show password'"
            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-gray-500 transition hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500"
            aria-controls="{{ $id }}"
        >
            <svg x-cloak x-show="!passwordVisible" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.5 7.5-9.75 7.5S2.25 12 2.25 12Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
            <svg x-cloak x-show="passwordVisible" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.5 10.5 0 0 0 2.25 12s3.5 7.5 9.75 7.5a10.4 10.4 0 0 0 5.02-1.28M6.23 6.23A10.4 10.4 0 0 1 12 4.5c6.25 0 9.75 7.5 9.75 7.5a10.6 10.6 0 0 1-2.47 3.53M6.23 6.23 3 3m3.23 3.23 4.15 4.15m7.37 7.37L21 21m-3.25-3.25-4.15-4.15m0 0a2.25 2.25 0 0 1-3.18-3.18" />
            </svg>
        </button>
    </div>
</div>
