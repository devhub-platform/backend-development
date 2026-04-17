<?php

return [
    'mfa' => [
        'enabled' => (bool) env('FILAMENT_MFA_ENABLED', true),
        'required' => (bool) env('FILAMENT_MFA_REQUIRED', false),
    ],
];

