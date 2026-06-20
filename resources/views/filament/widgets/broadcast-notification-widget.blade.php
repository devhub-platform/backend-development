<x-filament-widgets::widget class="fi-wi-widget">
    <div class="space-y-6">
        <!-- Header -->
        <div class="border-b border-gray-200 pb-4 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                📢 Broadcast Notifications
            </h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Send notifications to all users or specific user roles
            </p>
        </div>

        <!-- Form -->
        <form wire:submit="send" class="space-y-6">
            {{ $this->form }}

            <!-- Buttons -->
            <div class="flex gap-3">
                <x-filament::button
                    type="submit"
                    icon="heroicon-o-paper-airplane"
                    color="success"
                    class="flex-1"
                >
                    📤 Send Broadcast
                </x-filament::button>

                <x-filament::button
                    type="button"
                    wire:click="resetForm"
                    color="gray"
                    class="flex-1"
                >
                    🔄 Clear
                </x-filament::button>
            </div>

            <!-- Info Box -->
            <div class="rounded-lg border-l-4 border-blue-500 bg-blue-50 p-4 dark:bg-blue-900/20">
                <p class="text-sm text-blue-900 dark:text-blue-200">
                    <strong>💡 Note:</strong> Notifications will be stored in the database and visible to recipients in their notification panel.
                </p>
            </div>
        </form>
    </div>
</x-filament-widgets::widget>

