<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    | Options: 'gemini', 'openai', 'mock'
    */
    'provider' => env('AI_PROVIDER', 'gemini'),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
        'api_url' => 'https://generativelanguage.googleapis.com/v1beta/models',
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'api_url' => 'https://api.openai.com/v1/chat/completions',
    ],

    // Cache duration in minutes for AI analysis
    'cache_ttl' => env('AI_CACHE_TTL', 60),
];
