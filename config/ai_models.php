<?php

return [

    'post_chat' => 'google/gemini-2.5-flash',

    'chat' => [
        [
            'id'       => 'openai/gpt-oss-120b',
            'title'    => 'GPT OSS 120B',
            'best_for' => 'General Chat',
            'fallback' => 'openai/gpt-5-mini',
            'vision'   => true,
        ],
        [
            'id'       => 'qwen/qwen3-235b-a22b',
            'title'    => 'Qwen3 235B',
            'best_for' => 'Coding',
            'fallback' => 'qwen/qwen3-32b',
        ],
        [
            'id'       => 'deepseek/deepseek-v3.2-speciale',
            'title'    => 'DeepSeek V3.2',
            'best_for' => 'Deep Reasoning',
            'fallback' => 'deepseek/deepseek-v3.2',
        ],
        [
            'id'       => 'google/gemini-2.5-flash',
            'title'    => 'Gemini Flash',
            'best_for' => 'Summarization',
            'fallback' => 'google/gemini-2.5-flash-lite-preview-09-2025',
            'vision'   => true,
        ],
        [
            'id'       => 'x-ai/grok-4.1-fast',
            'title'    => 'Grok 4.1',
            'best_for' => 'Fast Knowledge',
            'fallback' => 'qwen/qwen3-32b',
            'vision'   => true,
        ],
        [
            'id'       => 'moonshotai/kimi-k2-thinking',
            'title'    => 'Kimi K2',
            'best_for' => 'Long Reasoning',
            'fallback' => 'deepseek/deepseek-r1-0528',
        ],

    ],
];
