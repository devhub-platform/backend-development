<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URL'),
        'token' => env('GITHUB_TOKEN'),
    ],

    'hackai' => [
        'base_url' => env('HACKAI_BASE_URL', 'https://ai.hackclub.com/proxy/v1'),
        'token' => env('HACKAI_API_KEY'),
        'embeddings_model' => env('HACKAI_EMBEDDINGS_MODEL', 'openai/text-embedding-3-large'),
        'embeddings_timeout' => (int)env('HACKAI_EMBEDDINGS_TIMEOUT', 20),
        'embeddings_enabled' => env('HACKAI_EMBEDDINGS_ENABLED', true),
    ],

    'hackclub_cdn' => [
        'base_url' => env('HACKCLUB_CDN_BASE_URL', 'https://cdn.hackclub.com/api/v4'),
        'token' => env('HACKCLUB_API_CDN'),
        'timeout' => (int)env('HACKCLUB_CDN_TIMEOUT', 30),
        'retry_times' => (int)env('HACKCLUB_CDN_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int)env('HACKCLUB_CDN_RETRY_SLEEP_MS', 200),
        'chat_fallback_to_s3' => (bool)env('HACKCLUB_CHAT_FALLBACK_TO_S3', true),
    ],


    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
    ],

    'mail' => [
        'admin_email_1' => env('ADMIN_EMAIL1'),
        'admin_email_2' => env('ADMIN_EMAIL2'),
    ],

    'piston' => [
        'api_key' => env('PISTON_API_KEY'),
        'base_url' => env('PISTON_API_URL', 'https://emkc.org/api/v2/piston'),
    ],

    'profile_share' => [
        'web_base_url' => env('PROFILE_SHARE_WEB_BASE_URL', env('APP_URL')),
        'deep_link_scheme' => env('PROFILE_SHARE_DEEP_LINK_SCHEME', 'devhub'),
        'deep_link_profile_path' => env('PROFILE_SHARE_DEEP_LINK_PROFILE_PATH', 'profile'),
    ],

    'ai_main_model' => [
        'base_url' => env('AI_MAIN_MODEL_BASE_URL'),
    ],
    'embedding' => [
        'base_url' => env('HACKAI_BASE_URL', 'https://ai.hackclub.com/proxy/v1'),
        'key' => env('HACKAI_API_KEY'),
        'model' => env('HACKAI_EMBEDDINGS_MODEL', 'qwen/qwen3-embedding-8b'),
    ],
];
