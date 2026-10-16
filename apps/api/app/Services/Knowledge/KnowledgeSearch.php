<?php

declare(strict_types=1);

namespace App\Services\Knowledge;

use App\Services\Ai\Embedder;
use App\Services\Ai\EmbeddingTask;
use App\Support\Tenancy\MissingTenantException;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\ConnectionInterface;

/**
 * Hybrid retrieval over the published, indexed passages of the current
 * tenant: Postgres full-text search (Portuguese) and pgvector cosine
 * similarity, combined by weighted scores or reciprocal rank fusion.
 *
 * Every query names the tenant explicitly in addition to Row-Level
 * Security, and only sees chunks embedded with the current model.
 */
final readonly class KnowledgeSearch
{
    /** Candidates taken from each ranking before combining them. */
    private const int CANDIDATES = 40;

    private const int RRF_K = 60;

    public function __construct(
        private Embedder $embedder,
        private TenantContext $tenants,
        private ConnectionInterface $db,
    ) {}

    /**
     * @return list<SearchHit>
     */
    public function search(string $query, SearchOptions $options = new SearchOptions): array
    {
        $tenantId = $this->tenants->id() ?? throw MissingTenantException::forModel(self::class);
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        [$vector] = $this->embedder->embed([$query], EmbeddingTask::Query);
        $rows = $this->candidates($tenantId, $query, '['.implode(',', $vector).']', $options);

        return $this->rank($rows, $options);
    }

    /**
     * @return list<object{id: string, article_id: string, title: string, ordinal: int, heading: string, content: string, text_score: float|string, semantic_score: float|string}>
     */
    private function candidates(string $tenantId, string $query, string $vector, SearchOptions $options): array
    {
        $filters = 'c.tenant_id = ? AND a.tenant_id = ? AND a.status = ? AND c.embedding_model = ?';
        $bindings = [$tenantId, $tenantId, 'published', $this->embedder->embeddingModel()];

        if ($options->customerVisibleOnly) {
            $filters .= ' AND a.visibility = ?';
            $bindings[] = 'customers';
        }

        if ($options->categoryId !== null) {
            $filters .= ' AND a.category_id = ?';
            $bindings[] = $options->categoryId;
        }

        // OR of the query's stems, so one matching word is enough to score.
        $sql = <<<SQL
            WITH query AS (
                SELECT NULLIF(replace(plainto_tsquery('portuguese', ?)::text, ' & ', ' | '), '') AS terms
            ),
            semantic AS (
                SELECT c.id, 1 - (c.embedding <=> ?::vector) AS score
                FROM kb_chunks c JOIN kb_articles a ON a.id = c.article_id
                WHERE {$filters}
                ORDER BY c.embedding <=> ?::vector
                LIMIT ?
            ),
            lexical AS (
                SELECT c.id, ts_rank_cd(c.search_vector, to_tsquery('portuguese', query.terms)) AS score
                FROM kb_chunks c JOIN kb_articles a ON a.id = c.article_id, query
                WHERE {$filters} AND query.terms IS NOT NULL AND c.search_vector @@ to_tsquery('portuguese', query.terms)
                ORDER BY score DESC
                LIMIT ?
            )
            SELECT c.id, c.article_id, a.published_title AS title, c.ordinal, c.heading, c.content,
                   COALESCE(l.score, 0) AS text_score,
                   COALESCE(s.score, 1 - (c.embedding <=> ?::vector)) AS semantic_score
            FROM (SELECT id FROM semantic UNION SELECT id FROM lexical) ids
            JOIN kb_chunks c ON c.id = ids.id
            JOIN kb_articles a ON a.id = c.article_id
            LEFT JOIN semantic s ON s.id = c.id
            LEFT JOIN lexical l ON l.id = c.id
            WHERE c.tenant_id = ?
            SQL;

        /** @var list<object{id: string, article_id: string, title: string, ordinal: int, heading: string, content: string, text_score: float|string, semantic_score: float|string}> */
        return $this->db->select($sql, [
            $query,
            $vector, ...$bindings, $vector, self::CANDIDATES,
            ...$bindings, self::CANDIDATES,
            $vector, $tenantId,
        ]);
    }

    /**
     * @param  list<object{id: string, article_id: string, title: string, ordinal: int, heading: string, content: string, text_score: float|string, semantic_score: float|string}>  $rows
     * @return list<SearchHit>
     */
    private function rank(array $rows, SearchOptions $options): array
    {
        if ($rows === []) {
            return [];
        }

        $text = array_map(fn (object $row): float => (float) $row->text_score, $rows);
        $semantic = array_map(fn (object $row): float => max(0.0, (float) $row->semantic_score), $rows);
        // ts_rank values are small and unbounded: scale them to the best match.
        $maxText = max($text) ?: 1.0;
        $text = array_map(fn (float $score): float => $score / $maxText, $text);

        $semanticRank = $this->ranks($semantic);
        $textRank = $this->ranks($text);
        $weight = max(0.0, min(1.0, $options->textWeight));

        $scores = [];

        foreach (array_keys($rows) as $i) {
            $scores[$i] = $options->rerank
                // Reciprocal rank fusion, scaled so a double first place is 1.
                ? ((1 / (self::RRF_K + $semanticRank[$i]) + ($text[$i] > 0 ? 1 / (self::RRF_K + $textRank[$i]) : 0)) * (self::RRF_K + 1) / 2)
                : $weight * $text[$i] + (1 - $weight) * $semantic[$i];
        }

        arsort($scores);
        $hits = [];
        $position = 0;

        foreach (array_slice($scores, 0, max(1, $options->topK), true) as $i => $score) {
            $row = $rows[$i];
            $hits[] = new SearchHit(
                chunkId: $row->id,
                articleId: $row->article_id,
                title: $row->title,
                ordinal: (int) $row->ordinal,
                heading: $row->heading,
                content: $row->content,
                textScore: $text[$i],
                semanticScore: $semantic[$i],
                score: min(1.0, $score),
                movement: $semanticRank[$i] - (++$position),
            );
        }

        return $hits;
    }

    /**
     * 1-based rank of each score (highest first).
     *
     * @param  array<int, float>  $scores
     * @return array<int, int>
     */
    private function ranks(array $scores): array
    {
        arsort($scores);
        $ranks = [];

        foreach (array_keys($scores) as $position => $i) {
            $ranks[$i] = $position + 1;
        }

        return $ranks;
    }
}
