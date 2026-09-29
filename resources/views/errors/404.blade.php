<x-layouts.guest title="Page not found">
    <div class="space-y-5 text-center">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-amber-100 text-3xl font-bold text-amber-700">404</div>
        <div>
            <h2 class="text-xl font-semibold text-slate-900">This page is missing</h2>
            <p class="mt-2 text-sm text-slate-600">The link may be broken or the page may have moved.</p>
        </div>
        <div class="flex justify-center gap-3">
            <a href="{{ route('login') }}" class="btn btn-primary">Go to login</a>
            <a href="{{ url('/') }}" class="btn btn-secondary">Back home</a>
        </div>
    </div>
</x-layouts.guest>
