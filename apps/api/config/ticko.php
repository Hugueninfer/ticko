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
    ],

    // API documentation (Swagger UI at /docs). Disabled in production unless enabled explicitly.
    'docs_enabled' => (bool) env('TICKO_DOCS_ENABLED', env('APP_ENV', 'production') !== 'production'),
    'openapi_path' => resource_path('openapi/openapi.json'),

    // E-mails of admins allowed into the Horizon dashboard outside the local environment.
    'horizon_admins' => array_values(array_filter(explode(',', (string) env('TICKO_HORIZON_ADMINS', '')))),
];
