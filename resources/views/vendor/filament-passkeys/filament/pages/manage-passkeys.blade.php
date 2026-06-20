<x-filament-panels::page.simple
    :heading="__('Passkeys')"
    :subheading="__('Passkeys let you sign in without a password using your device biometrics or a security key.')"
>
    @include('filament-passkeys::passkey-manager', ['passkeys' => $passkeys])
</x-filament-panels::page.simple>
