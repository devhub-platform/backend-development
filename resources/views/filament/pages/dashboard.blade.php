@php
    $widgets = $this->getWidgets();
\@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Dashboard Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                    Welcome back!
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Here's your platform analytics overview
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                    Last updated: {{ now()->format('M d, Y H:i') }}
                </span>
            </div>
        </div>

        {{-- Widgets Grid --}}
        <div class="grid gap-6">
            @foreach ($widgets as $widget)
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    @livewire($widget)
                </div>
            @endforeach
        </div>

        {{-- Quick Stats Footer --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-gradient-to-br from-blue-50 to-blue-100 p-4 dark:border-gray-700 dark:from-blue-900/20 dark:to-blue-800/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Platform Status</p>
                        <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">✓ Healthy</p>
                    </div>
                    <div class="text-3xl">🟢</div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-gradient-to-br from-green-50 to-green-100 p-4 dark:border-gray-700 dark:from-green-900/20 dark:to-green-800/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">API Status</p>
                        <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">✓ Online</p>
                    </div>
                    <div class="text-3xl">⚡</div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-gradient-to-br from-purple-50 to-purple-100 p-4 dark:border-gray-700 dark:from-purple-900/20 dark:to-purple-800/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">AI Service</p>
                        <p class="mt-1 text-2xl font-bold text-purple-600 dark:text-purple-400">✓ Active</p>
                    </div>
                    <div class="text-3xl">🤖</div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-gradient-to-br from-orange-50 to-orange-100 p-4 dark:border-gray-700 dark:from-orange-900/20 dark:to-orange-800/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Database</p>
                        <p class="mt-1 text-2xl font-bold text-orange-600 dark:text-orange-400">✓ Connected</p>
                    </div>
                    <div class="text-3xl">💾</div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

