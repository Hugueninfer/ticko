<?php

declare(strict_types=1);

return [
    // Base URI for RFC 7807 "type" members of error responses.
    'problem_base_uri' => env('TICKO_PROBLEM_BASE_URI', 'https://ticko.dev/problems'),

    // Lifetime of mobile/API bearer tokens.
    'token_ttl_minutes' => (int) env('TICKO_TOKEN_TTL_MINUTES', 60 * 24 * 30),

    // Private disk for ticket attachments and their signed URL lifetime.
    'attachments_disk' => env('TICKO_ATTACHMENTS_DISK', 'local'),
    'attachments_max_kb' => (int) env('TICKO_ATTACHMENTS_MAX_KB', 5120),
    'attachment_url_ttl_minutes' => (int) env('TICKO_ATTACHMENT_URL_TTL_MINUTES', 5),

    // Default SLA targets per priority, in minutes; categories may override them.
    'sla' => [
        'urgent' => ['first_response_minutes' => 15, 'resolution_minutes' => 4 * 60],
        'high' => ['first_response_minutes' => 60, 'resolution_minutes' => 8 * 60],
        'normal' => ['first_response_minutes' => 4 * 60, 'resolution_minutes' => 24 * 60],
        'low' => ['first_response_minutes' => 8 * 60, 'resolution_minutes' => 72 * 60],
    ],

    // Rate limits (requests per minute).
    'rate_limits' => [
        'auth' => (int) env('TICKO_RATE_LIMIT_AUTH', 5),
        'api' => (int) env('TICKO_RATE_LIMIT_API', 120),
        'uploads' => (int) env('TICKO_RATE_LIMIT_UPLOADS', 20),
        'demo' => (int) env('TICKO_RATE_LIMIT_DEMO', 30),
        // AI calls cost money and quota: suggestions and search playground, per user.
        'ai' => (int) env('TICKO_RATE_LIMIT_AI', 20),
    ],

    // API documentation (Swagger UI at /docs). Disabled in production unless enabled explicitly.
    'docs_enabled' => (bool) env('TICKO_DOCS_ENABLED', env('APP_ENV', 'production') !== 'production'),
    'openapi_path' => resource_path('openapi/openapi.json'),

    // One-click demo sign-in as the seeded agent/admin of the demo tenant (see `php artisan ticko:demo`).
    // Never available in production, whatever this flag says.
    'demo' => [
        'enabled' => (bool) env('TICKO_DEMO_LOGIN', false),
        'tenant' => env('TICKO_DEMO_TENANT', 'acme'),
    ],

    // AI: knowledge-base retrieval and reply suggestions. "fake" works offline
    // (development, demo, tests, CI). Keys live only in the local .env.
    'ai' => [
        'text_provider' => env('TICKO_AI_TEXT_PROVIDER', 'fake'),           // fake | gemini | groq | ollama
        'embedding_provider' => env('TICKO_AI_EMBEDDING_PROVIDER', 'fake'), // fake | gemini | ollama
        'timeout_seconds' => (int) env('TICKO_AI_TIMEOUT', 25),
        // Below this confidence the AI says it does not know instead of answering.
        'min_confidence' => (float) env('TICKO_AI_MIN_CONFIDENCE', 0.35),
        'top_k' => 5,
        // Retrieval: weight of the full-text score (the rest is semantic).
        'text_weight' => 0.4,
        'gemini' => [
            'url' => env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'key' => env('GEMINI_API_KEY'),
            'text_model' => env('GEMINI_TEXT_MODEL', 'gemini-2.5-flash'),
            'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
        ],
        'groq' => [
            'url' => env('GROQ_API_URL', 'https://api.groq.com/openai/v1'),
            'key' => env('GROQ_API_KEY'),
            'text_model' => env('GROQ_TEXT_MODEL', 'llama-3.3-70b-versatile'),
        ],
        'ollama' => [
            'url' => env('OLLAMA_URL', 'http://host.docker.internal:11434'),
            'text_model' => env('OLLAMA_TEXT_MODEL', 'llama3.1:8b'),
            'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
        ],
    ],

    // E-mails of admins allowed into the Horizon dashboard outside the local environment.
    'horizon_admins' => array_values(array_filter(explode(',', (string) env('TICKO_HORIZON_ADMINS', '')))),
];
