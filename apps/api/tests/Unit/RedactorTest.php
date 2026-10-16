<?php

declare(strict_types=1);

use App\Support\Telemetry\Redactor;

it('removes credentials and personal data from free text', function (string $text, string $expected): void {
    expect(Redactor::string($text))->toBe($expected);
})->with([
    'bearer token' => ['Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.abc', 'Authorization: Bearer [REDACTED]'],
    'sanctum token' => ['token 42|Zx9vQ8mN7bL6kJ5hG4fD3sA2pO1iU0yT', 'token [REDACTED]'],
    'ticko token' => ['saw ticko_Zx9vQ8mN7bL6kJ5hG4fD3sA2 in a log', 'saw [REDACTED] in a log'],
    'app key' => ['APP_KEY=base64:dGVzdGluZy1rZXktbm90LWEtc2VjcmV0LTAwMDAwMDA=', 'APP_KEY=[REDACTED]'],
    'google key' => ['key AIzaSyD-1234567890abcdefghijklmnopqrstu', 'key [REDACTED]'],
    'groq key' => ['gsk_abcdefghijklmnopqrstuvwxyz123456', '[REDACTED]'],
    'password assignment' => ['password=hunter2 user=x', 'password=[REDACTED] user=x'],
    'sql with values' => ['Query failed (SQL: insert into users (email) values (a@b.co))', 'Query failed (SQL: [REDACTED]'],
    'e-mail' => ['from mariana@example.com', 'from [EMAIL]'],
    'cpf' => ['cpf 123.456.789-09', 'cpf [CPF]'],
    'card' => ['cartão 4111 1111 1111 1111', 'cartão [CARTAO]'],
    'phone' => ['whats +55 11 98765 4321', 'whats [TELEFONE]'],
    'harmless' => ['Ticket 01JTICKET000000000000ABC821 assigned in 120 ms', 'Ticket 01JTICKET000000000000ABC821 assigned in 120 ms'],
]);

it('redacts values by key, recursively, and keeps numbers and flags', function (): void {
    expect(Redactor::array([
        'password' => 'x', 'current_password' => 'y', 'X-XSRF-TOKEN' => 'z', 'cookie' => 'c', 'x-goog-api-key' => 'k',
        'description' => 'texto do cliente', 'email' => 'a@b.co', 'name' => 'Mariana',
        'nested' => ['body' => 'oi', 'count' => 2, 'ok' => true],
        'route' => '/api/v1/tickets/{id}', 'input_tokens' => 1200, 'access_token' => 'abc',
        'user' => new stdClass,
    ]))->toBe([
        'password' => '[REDACTED]', 'current_password' => '[REDACTED]', 'X-XSRF-TOKEN' => '[REDACTED]', 'cookie' => '[REDACTED]', 'x-goog-api-key' => '[REDACTED]',
        'description' => '[REDACTED]', 'email' => '[REDACTED]', 'name' => '[REDACTED]',
        'nested' => ['body' => '[REDACTED]', 'count' => 2, 'ok' => true],
        'route' => '/api/v1/tickets/{id}', 'input_tokens' => 1200, 'access_token' => '[REDACTED]',
        'user' => 'stdClass',
    ]);
});
