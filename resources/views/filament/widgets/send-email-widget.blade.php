<x-filament::section>
    <x-slot name="heading">
        Send Email To User
    </x-slot>

    <x-slot name="description">
        Admin can send a direct email message to any user.
    </x-slot>

    <form wire:submit="send" class="space-y-4">
        {{ $this->form }}

        <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
            Send Email
        </x-filament::button>
    </form>
</x-filament::section>
