@if (session('status'))
    <div role="status" class="mb-4 flex items-start gap-2.5 rounded-md border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">
        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
        <p>{{ session('status') }}</p>
    </div>
@endif
@if (session('error'))
    <div role="alert" class="mb-4 flex items-start gap-2.5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v5M12 16.5v.01M12 21a9 9 0 100-18 9 9 0 000 18z"/></svg>
        <p>{{ session('error') }}</p>
    </div>
@endif
@if ($errors->any())
    <div role="alert" class="mb-4 flex items-start gap-2.5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v5M12 16.5v.01M12 21a9 9 0 100-18 9 9 0 000 18z"/></svg>
        @if ($errors->count() === 1)
            <p>{{ $errors->first() }}</p>
        @else
            <div>
                <p class="font-medium">Please fix these {{ $errors->count() }} things:</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
