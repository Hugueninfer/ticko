<?php

declare(strict_types=1);

namespace App\Support\Telemetry;

use App\Support\Privacy\PiiMasker;
use Stringable;
use Throwable;

/**
 * Removes secrets and personal data from anything that leaves the process
 * as telemetry (log messages and context, span attributes).
 *
 *  - Keys that name a secret (password, token, cookie, authorization, api
 *    key...) or customer content (ticket body, description, subject, e-mail...)
 *    have their value replaced, whatever it is.
 *  - In every other string: credentials (bearer and Sanctum tokens, provider
 *    keys, the app key), SQL with bound values, and personal data (e-mail,
 *    CPF, CNPJ, card, phone) are replaced.
 *
 * The Collector applies a second, independent redaction before storage.
 */
final class Redactor
{
    public const string REDACTED = '[REDACTED]';

    /** Key fragments of secrets (matched anywhere in the lower-cased key). */
    private const array SECRET_KEY_PARTS = ['pass', 'secret', 'token', 'auth', 'cookie', 'apikey', 'api_key', 'api-key', 'credential', 'signature', 'session', 'csrf', 'xsrf'];

    /** Keys whose value is customer content or personal data. */
    private const array CONTENT_KEYS = [
        'body', 'description', 'subject', 'content', 'text', 'markdown', 'answer', 'quote', 'excerpt', 'question',
        'email', 'phone', 'cpf', 'cnpj', 'card', 'address', 'name', 'first_name', 'last_name', 'user_name', 'requester_name',
    ];

    /** @var array<string, string> */
    private const array CREDENTIALS = [
        '/\bBearer\s+[A-Za-z0-9._~+\/=|-]+/i' => 'Bearer '.self::REDACTED,
        '/\b\d+\|[A-Za-z0-9]{20,}\b/' => self::REDACTED,             // Sanctum plain-text token
        '/\bticko_[A-Za-z0-9]{16,}\b/' => self::REDACTED,             // Ticko token prefix
        '/\bbase64:[A-Za-z0-9+\/=]{20,}/' => self::REDACTED,          // APP_KEY
        '/\bAIza[0-9A-Za-z_-]{30,}\b/' => self::REDACTED,             // Google API key
        '/\bgsk_[A-Za-z0-9]{20,}\b/' => self::REDACTED,               // Groq API key
        '/\beyJ[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]+/' => self::REDACTED, // JWT
        '/\b(password|passwd|secret|token|api[_-]?key)\s*[=:]\s*\S+/i' => '$1='.self::REDACTED,
        // Query exceptions carry the SQL with its bound values (which may be ticket text).
        '/\bSQL: .*/s' => 'SQL: '.self::REDACTED,
    ];

    public static function string(string $value): string
    {
        $value = (string) preg_replace(array_keys(self::CREDENTIALS), array_values(self::CREDENTIALS), $value);

        return PiiMasker::mask($value);
    }

    public static function value(mixed $value, ?string $key = null, int $depth = 0): mixed
    {
        if ($key !== null && self::isSensitiveKey($key)) {
            return self::REDACTED;
        }

        return match (true) {
            $value === null, is_bool($value), is_int($value), is_float($value) => $value,
            is_string($value) => self::string($value),
            $value instanceof Throwable => self::exception($value),
            is_array($value) => $depth >= 4 ? self::REDACTED : self::array($value, $depth + 1),
            $value instanceof Stringable => self::string((string) $value),
            is_object($value) => $value::class,
            default => self::REDACTED,
        };
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    public static function array(array $values, int $depth = 0): array
    {
        $clean = [];

        foreach ($values as $key => $value) {
            $clean[$key] = self::value($value, is_string($key) ? $key : null, $depth);
        }

        return $clean;
    }

    /**
     * An exception as telemetry: its type and a redacted message.
     *
     * @return array{type: class-string<Throwable>, message: string}
     */
    public static function exception(Throwable $exception): array
    {
        return ['type' => $exception::class, 'message' => self::string($exception->getMessage())];
    }

    public static function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);

        if (in_array($key, self::CONTENT_KEYS, true)) {
            return true;
        }

        foreach (self::SECRET_KEY_PARTS as $part) {
            if (str_contains($key, $part)) {
                return true;
            }
        }

        return false;
    }
}
