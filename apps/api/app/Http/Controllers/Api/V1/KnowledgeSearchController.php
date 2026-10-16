<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Knowledge\SearchKnowledgeRequest;
use App\Models\KbArticle;
use App\Services\Ai\AiUnavailable;
use App\Services\Ai\Embedder;
use App\Services\Knowledge\KnowledgeSearch;
use App\Services\Knowledge\SearchHit;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

#[Group('Knowledge base')]
final class KnowledgeSearchController extends Controller
{
    /**
     * Search the knowledge base (playground)
     *
     * Hybrid search exactly as the AI uses it: full-text and semantic scores
     * and the combined score of each passage.
     */
    #[ScribeResponse(['data' => [['chunk_id' => '01J...', 'article_id' => '01J...', 'title' => 'Política de entregas e prazos', 'ordinal' => 3, 'heading' => 'Rastreio sem atualização', 'content' => 'Se o rastreamento não for atualizado...', 'text_score' => 0.62, 'semantic_score' => 0.89, 'score' => 0.91, 'movement' => 1]], 'meta' => ['latency_ms' => 212, 'embedding_model' => 'gemini/gemini-embedding-001']])]
    public function __invoke(SearchKnowledgeRequest $request, KnowledgeSearch $search, Embedder $embedder): JsonResponse
    {
        Gate::authorize('viewAny', KbArticle::class);

        $started = hrtime(true);

        try {
            $hits = $search->search($request->string('query')->toString(), $request->options());
        } catch (AiUnavailable $exception) {
            throw new ServiceUnavailableHttpException(30, "The search service is unavailable ({$exception->errorCode}).");
        }

        return response()->json([
            'data' => array_map(fn (SearchHit $hit): array => $hit->toArray(), $hits),
            'meta' => [
                'latency_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
                'embedding_model' => $embedder->embeddingModel(),
            ],
        ]);
    }
}
