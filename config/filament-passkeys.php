<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Render hook
    |--------------------------------------------------------------------------
    |
    | Where the "Sign in with passkey" button is injected on the Filament
    | login page. See \Filament\View\PanelsRenderHook for available hooks.
    |
    */
    'login_render_hook' => \Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,

    /*
    |--------------------------------------------------------------------------
    | Label overrides
    |--------------------------------------------------------------------------
    |
    | These let you override the default labels without publishing the
    | translation file. Leave null to use the published translations.
    |
    */
    'login_button_label' => null,
    'user_menu_item_label' => null,
];
