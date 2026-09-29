<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="animate-float-slow absolute -left-16 top-10 h-52 w-52 rounded-full bg-emerald-200/50 blur-3xl"></div>
        <div class="animate-float-slow absolute -right-12 top-8 h-56 w-56 rounded-full bg-sky-200/60 blur-3xl" style="animation-delay: 1.2s;"></div>
    </div>

    <main class="relative flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-sm animate-fade-in-up">
            <div class="mb-7 text-center">
                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-brand-600 text-white shadow-[0_16px_30px_-18px_rgba(22,163,74,0.85)]">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5c-.3 0-.6.15-.8.4C9 6 5.5 10 5.5 14a6.5 6.5 0 0013 0c0-4-3.5-8-5.7-11.1a1 1 0 00-.8-.4z"/></svg>
                </span>
                <p class="text-xl font-semibold tracking-tight text-slate-900">{{ config('app.name') }}</p>
                <p class="mt-1 text-sm text-slate-600">Online water delivery &amp; refilling station</p>
            </div>
            <div class="card p-6 sm:p-7">
                <h1 class="mb-5 text-base font-semibold text-slate-900">{{ $title ?? '' }}</h1>
                <x-flash />
                {{ $slot }}
            </div>
        </div>
    </main>
</body>
</html>
