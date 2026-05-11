<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default model
    |--------------------------------------------------------------------------
    */
    'post_chat'     => 'google/gemini-2.5-flash',
    'question_chat' => 'google/gemini-2.5-flash',
    'default'       => 'google/gemini-2.5-flash',

    /*
    |--------------------------------------------------------------------------
    | Per-user prompt quotas
    |--------------------------------------------------------------------------
    | Set a value to 0 to disable that limit entirely.
    */
    'prompt_limits' => [
        'daily'   => env('PROMPT_LIMIT_DAILY',   50),
        'monthly' => env('PROMPT_LIMIT_MONTHLY', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat models
    |--------------------------------------------------------------------------
    |
    | vision: true  → model can analyse images sent as base64 / URL
    |                 images are included in the request payload
    |
    | vision: false (or missing) → model does NOT support images
    |                 if the user attaches an image the API will return a
    |                 clear error message asking them to use a vision model
    |                 or send documents instead
    |
    */
    'chat' => [
        [
            'id'       => 'openai/gpt-oss-120b',
            'title'    => 'ChatGPT OSS',
            'best_for' => 'General Chat',
            'fallback' => 'openai/gpt-5-mini',
            'vision'   => false,   // does not support image input on HackAI
            'cost'     => 0.03,
        ],
        [
            'id'       => 'qwen/qwen3-235b-a22b',
            'title'    => 'Qwen3 235B',
            'best_for' => 'Coding',
            'fallback' => 'qwen/qwen3-32b',
            'vision'   => false,
            'cost'     => 0.025,
        ],
        [
            'id'       => 'deepseek/deepseek-v3.2-speciale',
            'title'    => 'DeepSeek V3.2',
            'best_for' => 'Deep Reasoning',
            'fallback' => 'deepseek/deepseek-v3.2',
            'vision'   => false,
            'cost'     => 0.02,
        ],
        [
            'id'       => 'google/gemini-2.5-flash',
            'title'    => 'Gemini Flash',
            'best_for' => 'Summarization',
            'fallback' => 'google/gemini-2.5-flash-lite-preview-09-2025',
            'vision'   => true,    // confirmed: supports image input
            'cost'     => 0.018,
        ],
        [
            'id'       => 'x-ai/grok-4.1-fast',
            'title'    => 'Grok 4.1',
            'best_for' => 'Fast Knowledge',
            'fallback' => 'qwen/qwen3-32b',
            'vision'   => true,    // confirmed: supports image input
            'cost'     => 0.022,
        ],
        [
            'id'       => 'moonshotai/kimi-k2-thinking',
            'title'    => 'Kimi K2',
            'best_for' => 'Long Reasoning',
            'fallback' => 'deepseek/deepseek-r1-0528',
            'vision'   => false,
            'cost'     => 0.025,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image generation models
    |--------------------------------------------------------------------------
    */
    'image' => [
        [
            'id'       => 'google/gemini-2.5-flash-image-preview',
            'title'    => 'Nano Banana',
            'best_for' => 'Fast image generation',
            'cost'     => 0.04,
            'default'  => true,
        ],
        [
            'id'       => 'google/gemini-2.5-pro-image-preview',
            'title'    => 'Nano Banana 2',
            'best_for' => 'High quality image generation',
            'cost'     => 0.07,
            'default'  => false,
        ],
    ],

];
