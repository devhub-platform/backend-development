<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />

        <meta name="application-name" content="{{ config('app.name') }}" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <title>{{ config('app.name') }}</title>

        <style>
            /* Ensure Alpine's x-cloak works when used by Filament components */
            [x-cloak] {
                display: none !important;
            }
        </style>

        @filamentStyles
        @vite('resources/css/app.css')
    </head>

    <body class="antialiased bg-gray-50 text-gray-800">
        <div class="min-h-screen flex flex-col">
            <header class="bg-white border-b">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">
                        <div class="flex items-center space-x-3">
                            <a href="{{ url('/') }}" class="flex items-center text-lg font-semibold text-gray-800">
                                <img src="{{ asset('favicon.ico') }}" alt="{{ config('app.name') }}" class="h-8 w-8 mr-2" />
                                <span class="hidden sm:inline">{{ config('app.name') }}</span>
                            </a>
                        </div>

                                                <div class="flex items-center space-x-4">
                                                    {{-- Sidebar toggle (will be handled by Filament's JS) --}}
                                                    <button type="button" id="filament-sidebar-toggle" class="filament-button hidden md:inline-flex">
                                                        <span class="sr-only">Toggle sidebar</span>
                                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                                        </svg>
                                                    </button>

                                                    {{-- Admin info: avatar, name and simple dropdown --}}
                                                    @auth
                                                        @php $user = auth()->user(); @endphp
                                                        <div class="relative" x-data="{ open: false }">
                                                            <button @click.prevent="open = !open" class="flex items-center space-x-2 rounded-md px-2 py-1 hover:bg-gray-100">
                                                                <img src="{{ $user->getFilamentAvatarUrl() ?? asset('favicon.ico') }}" alt="{{ $user->name }}" class="h-8 w-8 rounded-full object-cover" />
                                                                <span class="hidden sm:inline text-sm font-medium">{{ $user->name }}</span>
                                                                <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                                </svg>
                                                            </button>

                                                            <div x-show="open" @click.away="open = false" style="display: none;" class="absolute right-0 mt-2 w-48 bg-white border rounded-md shadow-lg z-50 py-1">
                                                                <div class="px-3 py-2 text-sm text-gray-700">
                                                                    <div class="font-medium">{{ $user->name }}</div>
                                                                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                                                </div>

                                                                <div class="border-t"></div>

                                                                <form method="POST" action="{{ route('logout') }}" class="p-2">
                                                                    @csrf
                                                                    <button type="submit" class="w-full text-left text-sm text-red-600 hover:bg-gray-50 rounded px-2 py-1">Logout</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    @endauth
                                                </div>
                    </div>
                </div>
            </header>

            <main class="flex-1">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    {{ $slot }}
                </div>
            </main>

            <footer class="bg-white border-t">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-sm text-gray-500">
                    © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </div>
            </footer>
        </div>

        @filamentScripts
        @vite('resources/js/app.js')
    </body>
</html>

