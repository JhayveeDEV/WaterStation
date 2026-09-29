<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-gray-50" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    <script>
        window.AppUserId = {{ auth()->id() ?? 'null' }};
        window.AppIsStaff = {{ auth()->user()?->hasAnyRole(['staff','admin']) ? 'true' : 'false' }};
        window.AppEnableRealtime = {{ env('VITE_ENABLE_REVERB', false) ? 'true' : 'false' }};
    </script>

    <a href="#main" class="sr-only rounded-md bg-white px-3 py-2 text-sm font-medium text-brand-700 shadow focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[60]">Skip to content</a>

    <div class="flex min-h-screen">
        {{-- Sidebar (desktop) --}}
        <aside class="hidden w-72 shrink-0 border-r border-gray-200 bg-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col">
            @include('layouts.partials.brand')
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Main">
                @include('layouts.partials.nav-'.auth()->user()->getRoleNameAttribute())
            </nav>
            @include('layouts.partials.sidebar-footer')
        </aside>

        {{-- Sidebar (mobile) --}}
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 lg:hidden">
            <div class="fixed inset-0 bg-gray-900/40" @click="sidebarOpen = false"></div>
            <aside class="relative flex h-full w-64 max-w-[85vw] flex-col bg-white shadow-xl"
                   x-show="sidebarOpen" x-transition:enter="transition duration-150" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0">
                <button class="absolute right-2 top-3 rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" @click="sidebarOpen = false" aria-label="Close menu">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
                @include('layouts.partials.brand')
                <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-4" aria-label="Main">
                    @include('layouts.partials.nav-'.auth()->user()->getRoleNameAttribute())
                </nav>
                @include('layouts.partials.sidebar-footer')
            </aside>
        </div>

        {{-- Main --}}
        <div class="flex min-h-screen min-w-0 flex-1 flex-col">
            <div id="connection-banner" class="hidden border-b border-amber-200 bg-amber-50 px-4 py-1.5 text-center text-xs text-amber-800">
                Reconnecting. Live updates are paused for a moment.
            </div>
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-gray-200 bg-white/95 px-4 backdrop-blur lg:px-6">
                <div class="flex items-center gap-3">
                    <button class="-ml-1.5 rounded-md p-1.5 text-gray-500 hover:bg-gray-100 lg:hidden" @click="sidebarOpen = true" aria-label="Open menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1 class="text-lg font-semibold tracking-tight text-gray-900">{{ $title ?? '' }}</h1>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    @include('layouts.partials.notification-bell')

                    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
                        <button @click="open = !open" :aria-expanded="open" aria-haspopup="true"
                                class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 text-sm text-gray-700 transition-colors hover:bg-gray-100">
                            <span class="avatar">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="hidden font-medium sm:block">{{ auth()->user()->name }}</span>
                            <svg class="hidden h-4 w-4 text-gray-400 sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition.opacity.duration.100ms @click.outside="open = false"
                             class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg">
                            <div class="border-b border-gray-100 px-4 py-3">
                                <p class="truncate text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="py-1">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">My account</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">Sign out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main id="main" class="mx-auto w-full max-w-[1400px] flex-1 p-5 lg:p-7">
                <x-flash />
                {{ $slot }}
            </main>
        </div>
    </div>

    <div id="toast-host" class="fixed bottom-4 right-4 z-50 flex flex-col gap-2"></div>
    @stack('scripts')
</body>
</html>
