<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme-scope="app">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" type="image/webp" href="{{ asset('images/logo.webp') }}?v={{ filemtime(public_path('images/logo.webp')) }}">

        <title>{{ config('app.name', 'Rumitech') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Theme (applied before paint to avoid a flash of light mode) -->
        <script>
            (function () {
                try {
                    var mode = localStorage.getItem('theme') || 'system';
                    var dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

                    if (dark) {
                        document.documentElement.classList.add('dark');
                    }

                    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
                } catch (error) {
                    // Storage can be unavailable; fall back to the light theme.
                }
            })();
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div
            x-data="{
                sidebarOpen: false,
                sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
                isDesktop: window.innerWidth >= 1024,
                init() {
                    this.$watch('sidebarCollapsed', (value) => localStorage.setItem('sidebarCollapsed', value));
                },
            }"
            @resize.window="isDesktop = window.innerWidth >= 1024"
            class="min-h-screen bg-blue-50"
        >
            @include('layouts.navigation')

            <div :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72'" class="transition-[padding] duration-300">
                <!-- Top bar -->
                <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-blue-100 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="-ml-2 inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 transition hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 lg:hidden"
                        aria-controls="app-sidebar"
                    >
                        <span class="sr-only">Buka menu</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <a href="{{ Auth::user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}" class="flex items-center gap-2.5 lg:hidden">
                        <x-application-logo class="h-8 w-8 rounded-xl object-cover shadow-sm shadow-blue-600/20 ring-1 ring-blue-100" />
                        <span class="text-sm font-semibold tracking-tight text-slate-900">Rumitech</span>
                    </a>

                    <p class="hidden text-sm font-semibold tracking-tight text-slate-900 lg:block">
                        Rumitech
                        <span class="font-normal text-slate-400">/ Warehouse Management System</span>
                    </p>

                    <div class="ml-auto flex items-center gap-2">
                        <x-notification-dropdown />
                        <x-profile-dropdown />
                    </div>
                </header>

                <x-announcement-banner />

                <!-- Page Heading -->
                @isset($header)
                    <header class="border-b border-blue-100 bg-white">
                        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-welcome-toast />
        <x-flash-popup />
    </body>
</html>
