<?php

declare(strict_types=1);

namespace App\Services\Classifier;

use App\Enums\Sentiment;
use App\Enums\TicketPriority;
use App\Support\Privacy\PiiMasker;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;

/**
 * Client of the internal classifier service (services/classifier).
 *
 * The text is masked (personal data) and bounded before it is sent. The
 * answer is untrusted: every label must belong to a closed set and every
 * confidence must be a number between 0 and 1, or it is rejected.
 */
final readonly class ClassifierClient
{
    public const array CATEGORIES = ['entrega', 'pagamento', 'troca_devolucao', 'conta_acesso', 'produto', 'outro'];

    private const array FLAGS = ['instructions_removed', 'pii_masked'];

    private const int MAX_CHARS = 8000;

    public function __construct(
        private Http $http,
        private string $url,
        private string $token,
        private int $timeoutSeconds,
    ) {}

    public function classify(string $text): Classification
    {
        if ($this->url === '' || $this->token === '') {
            throw new ClassifierUnavailable('NOT_CONFIGURED');
        }

        try {
            $response = $this->http->acceptJson()->asJson()->withToken($this->token)->timeout($this->timeoutSeconds)->connectTimeout(3)
                ->post(rtrim($this->url, '/').'/v1/classify', ['text' => mb_substr(PiiMasker::mask($text), 0, self::MAX_CHARS)]);
        } catch (ConnectionException) {
            throw new ClassifierUnavailable('TIMEOUT');
        }

        if (! $response->successful()) {
            throw new ClassifierUnavailable('HTTP_'.$response->status());
        }

        return $this->parse($response->json());
    }

    private function parse(mixed $json): Classification
    {
        if (! is_array($json)) {
            throw new ClassifierUnavailable('INVALID_RESPONSE');
        }

        $model = $json['model'] ?? null;
        $category = $this->label($json, 'category', self::CATEGORIES);
        $priority = $this->label($json, 'priority', array_column(TicketPriority::cases(), 'value'));
        $sentiment = $this->label($json, 'sentiment', array_column(Sentiment::cases(), 'value'));
        $flags = $json['flags'] ?? [];

        if (! is_string($model) || preg_match('/^[\w.-]{1,64}$/', $model) !== 1 || ! is_array($flags) || array_filter($flags, is_string(...)) !== $flags) {
            throw new ClassifierUnavailable('INVALID_RESPONSE');
        }

        return new Classification(
            model: $model,
            category: $category[0],
            categoryConfidence: $category[1],
            priority: TicketPriority::from($priority[0]),
            priorityConfidence: $priority[1],
            sentiment: Sentiment::from($sentiment[0]),
            sentimentConfidence: $sentiment[1],
            flags: array_values(array_intersect(self::FLAGS, $flags)),
        );
    }

    /**
     * @param  array<mixed>  $json
     * @param  list<string>  $allowed
     * @return array{string, float}
     */
    private function label(array $json, string $task, array $allowed): array
    {
        $result = $json[$task] ?? null;
        $label = is_array($result) ? ($result['label'] ?? null) : null;
        $confidence = is_array($result) ? ($result['confidence'] ?? null) : null;

        if (! is_string($label) || ! in_array($label, $allowed, true) || ! (is_float($confidence) || is_int($confidence)) || $confidence < 0 || $confidence > 1) {
            throw new ClassifierUnavailable('INVALID_RESPONSE');
        }

        return [$label, (float) $confidence];
    }
}
