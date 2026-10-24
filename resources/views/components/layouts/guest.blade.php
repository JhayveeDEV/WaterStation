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
    <main class="min-h-screen lg:grid lg:grid-cols-2">
        <section class="relative isolate flex min-h-[280px] flex-col justify-between overflow-hidden bg-brand-900 px-6 py-8 text-white sm:px-10 sm:py-10 lg:min-h-screen lg:px-14 lg:py-12">
            <div class="pointer-events-none absolute -right-24 -top-24 -z-10 h-80 w-80 rounded-full bg-brand-700/60 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -left-24 -z-10 h-96 w-96 rounded-full bg-brand-600/40 blur-3xl"></div>

            <a href="{{ route('login') }}" class="flex w-fit items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/20">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5c-.3 0-.6.15-.8.4C9 6 5.5 10 5.5 14a6.5 6.5 0 0013 0c0-4-3.5-8-5.7-11.1a1 1 0 00-.8-.4z"/></svg>
                </span>
                <span>
                    <span class="block text-base font-semibold tracking-tight">{{ config('app.name') }}</span>
                    <span class="block text-xs text-white/65">Water delivery &amp; refilling station</span>
                </span>
            </a>

            <div class="max-w-xl py-10 lg:py-16">
                <p class="mb-4 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200">A better way to stay supplied</p>
                <h2 class="max-w-lg text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">
                    Fresh water, made easy.
                </h2>
                <p class="mt-5 max-w-md text-base leading-7 text-white/75 sm:text-lg">
                    Sign in to manage your water station account and keep your day moving with one less thing to worry about.
                </p>

                <div class="mt-8 flex flex-wrap gap-3 text-sm text-white/85">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3.5 py-2">
                        <svg class="h-4 w-4 text-emerald-200" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.2 7.2a1 1 0 01-1.415.005l-3.8-3.7a1 1 0 111.395-1.435l3.093 3.012 6.51-6.502a1 1 0 011.417 0z" clip-rule="evenodd"/></svg>
                        Simple account access
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3.5 py-2">
                        <svg class="h-4 w-4 text-emerald-200" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-12a.75.75 0 00-1.5 0v4.25c0 .25.125.484.334.624l2.5 1.667a.75.75 0 10.832-1.248l-2.166-1.445V6z" clip-rule="evenodd"/></svg>
                        Convenient ordering
                    </span>
                </div>
            </div>

            <p class="text-xs text-white/55">Here to keep your water needs running smoothly.</p>
        </section>

        <section class="flex items-center justify-center px-4 py-10 sm:px-8 lg:px-12">
            <div class="w-full max-w-md animate-fade-in-up">
                <div class="card p-6 sm:p-8">
                    <div class="mb-6">
                        <p class="text-sm font-medium text-brand-700">{{ config('app.name') }}</p>
                        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $title ?? '' }}</h1>
                        <p class="mt-2 text-sm text-slate-500">Enter your details to continue.</p>
                    </div>
                    <x-flash />
                    {{ $slot }}
                </div>
            </div>
        </section>
    </main>
</body>
</html>
