<x-filament::section
    :heading="__('Passkeys')"
    :description="__('Sign in without a password using your device biometrics or a security key.')"
    icon="heroicon-o-key"
    compact
    divided
    secondary
    style="margin-top: calc(var(--spacing) * 6);"
>
    @include('filament-passkeys::passkey-manager', ['passkeys' => $passkeys])
</x-filament::section>
