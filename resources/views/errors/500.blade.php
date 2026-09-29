<x-layouts.guest title="Something went wrong">
    <div class="space-y-5 text-center">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-rose-100 text-3xl font-bold text-rose-700">500</div>
        <div>
            <h2 class="text-xl font-semibold text-slate-900">We hit a server error</h2>
            <p class="mt-2 text-sm text-slate-600">Please try again in a moment, or return to the home screen.</p>
        </div>
        <div class="flex justify-center gap-3">
            <a href="{{ url('/') }}" class="btn btn-primary">Return home</a>
            <a href="{{ route('login') }}" class="btn btn-secondary">Login again</a>
        </div>
    </div>
</x-layouts.guest>
