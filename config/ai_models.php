<?php

return [
    'chat' => [
        [
            'id' => 'openai/gpt-5.1',
            'title' => 'GPT‑5.1',
            'best_for' => 'General Chat',
            'fallback' => 'openai/gpt-5.1-mini',
        ],
        [
            'id' => 'qwen/qwen3-next-80b-a3b-instruct',
            'title' => 'Qwen3',
            'best_for' => 'Coding',
            'fallback' => 'qwen/qwen3-mini',
        ],
        [
            'id' => 'deepseek/deepseek-v3.2-special',
            'title' => 'DeepSeek',
            'best_for' => 'Deep Reasoning',
            'fallback' => 'deepseek-v3.2-mini',
        ],
        [
            'id' => 'google/gemini-3-pro-preview',
            'title' => 'Gemini Pro',
            'best_for' => 'Summarization',
            'fallback' => 'google/gemini-3-pro-mini',
        ],
        [
            'id' => 'x-ai/grok-4.1-fast',
            'title' => 'Grok',
            'best_for' => 'Fast Knowledge',
            'fallback' => 'x-ai/grok-4.1-mini',
        ],
    ],
];
