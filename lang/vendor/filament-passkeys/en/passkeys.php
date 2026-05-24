<?php

return [
    'login' => [
        'button' => 'Sign in with passkey',
        'signing_in' => 'Signing in…',
        'or' => 'or',
        'failure' => 'Passkey sign in failed.',
    ],

    'section' => [
        'heading' => 'Passkeys',
        'description' => 'Sign in without a password using your device biometrics or a security key.',
    ],

    'list' => [
        'added' => 'Added :date',
        'just_now' => 'Added just now',
    ],

    'empty' => [
        'heading' => 'No passkeys yet',
        'description' => 'Add one below to sign in without a password.',
    ],

    'form' => [
        'label' => 'Add a new passkey',
        'name_placeholder' => 'e.g. MacBook Touch ID',
        'submit' => 'Add passkey',
        'adding' => 'Adding…',
        'success' => 'Passkey added.',
    ],

    'remove' => [
        'button' => 'Remove',
        'heading' => 'Remove passkey?',
        'description' => 'You will no longer be able to sign in with this passkey.',
        'success' => 'Passkey removed.',
        'failure' => 'Could not remove passkey.',
    ],

    'errors' => [
        'exists' => 'That device already has a passkey for this account.',
        'add_failure' => 'Could not add passkey.',
        'not_supported_heading' => 'Passkeys not supported',
        'not_supported' => 'Your browser does not support passkeys. Try a recent version of Chrome, Safari, Edge, or Firefox.',
        'invalid_domain' => 'Passkeys cannot be used on this domain. Make sure you are on the same origin as the app.',
    ],

    'password_confirm' => [
        'heading' => 'Confirm your password',
        'description' => 'For your security, please confirm your password to continue.',
        'label' => 'Password',
        'submit' => 'Confirm',
        'cancel' => 'Cancel',
        'invalid' => 'The password is incorrect.',
        'network_error' => 'Could not verify password. Please try again.',
    ],

    'menu' => [
        'label' => 'Passkeys',
    ],
];
