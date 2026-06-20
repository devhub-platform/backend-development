<x-filament::section>
    <x-slot name="heading">
        Send Email To User
    </x-slot>

    <x-slot name="description">
        Send a direct message from the dashboard without leaving analytics.
    </x-slot>

    <form wire:submit="send" class="space-y-5">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                Send Email
            </x-filament::button>
        </div>
    </form>
</x-filament::section>