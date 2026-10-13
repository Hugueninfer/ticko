<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Renders API errors as RFC 7807 / RFC 9457 "problem details" documents.
 *
 * Internal details (messages, traces) are only exposed when app.debug is on,
 * and never for 5xx responses in production.
 */
final class ProblemDetailsRenderer
{
    public const string CONTENT_TYPE = 'application/problem+json';

    public function __invoke(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        return match (true) {
            $e instanceof ValidationException => $this->problem(
                $request, 422, 'validation-error', 'The given data was invalid.',
                'One or more fields failed validation.', ['errors' => $e->errors()],
            ),
            $e instanceof AuthenticationException => $this->problem(
                $request, 401, 'unauthenticated', 'Unauthenticated.', 'Valid credentials are required.',
            ),
            $e instanceof AuthorizationException => $this->problem(
                $request, 403, 'forbidden', 'Forbidden.', 'You are not allowed to perform this action.',
            ),
            $e instanceof ModelNotFoundException => $this->problem(
                $request, 404, 'not-found', 'Not Found.', 'The requested resource does not exist.',
            ),
            $e instanceof ThrottleRequestsException => $this->problem(
                $request, 429, 'too-many-requests', 'Too Many Requests.', 'Rate limit exceeded. Retry later.',
                headers: $this->headers($e),
            ),
            $e instanceof HttpExceptionInterface => $this->fromHttpException($request, $e),
            default => $this->problem(
                $request, 500, 'server-error', 'Server Error.',
                config('app.debug') === true ? $e->getMessage() : 'An unexpected error occurred.',
            ),
        };
    }

    /**
     * @param  HttpExceptionInterface&Throwable  $e
     */
    private function fromHttpException(Request $request, HttpExceptionInterface $e): JsonResponse
    {
        $status = $e->getStatusCode();
        $title = Response::$statusTexts[$status] ?? 'Error';
        // 404 messages may name internal model classes ("No query results for model ..."),
        // so only safe, non-404 client error messages are passed through.
        $detail = match (true) {
            $status === 404 => 'The requested resource does not exist.',
            $status < 500 && $e->getMessage() !== '' => $e->getMessage(),
            default => $title.'.',
        };
        $slug = match ($status) {
            401 => 'unauthenticated',
            403 => 'forbidden',
            404 => 'not-found',
            409 => 'conflict',
            419 => 'csrf-token-mismatch',
            default => 'http-'.$status,
        };

        return $this->problem($request, $status, $slug, $title.'.', $detail, headers: $this->headers($e));
    }

    /**
     * @return array<string, string|list<string>>
     */
    private function headers(HttpExceptionInterface $e): array
    {
        /** @var array<string, string|list<string>> */
        return $e->getHeaders();
    }

    /**
     * @param  array<string, mixed>  $extensions
     * @param  array<string, string|list<string>>  $headers
     */
    private function problem(
        Request $request,
        int $status,
        string $slug,
        string $title,
        string $detail,
        array $extensions = [],
        array $headers = [],
    ): JsonResponse {
        $body = [
            'type' => rtrim(config()->string('ticko.problem_base_uri'), '/').'/'.$slug,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => '/'.ltrim($request->path(), '/'),
            ...$extensions,
        ];

        return new JsonResponse($body, $status, [...$headers, 'Content-Type' => self::CONTENT_TYPE], JSON_UNESCAPED_SLASHES);
    }
}
