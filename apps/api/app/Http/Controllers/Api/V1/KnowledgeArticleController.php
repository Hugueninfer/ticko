<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Knowledge\PublishKnowledgeArticle;
use App\Actions\Knowledge\ReindexKnowledgeArticle;
use App\Actions\Knowledge\SaveKnowledgeArticle;
use App\Actions\Knowledge\UnpublishKnowledgeArticle;
use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Enums\IndexStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Knowledge\ImportArticleRequest;
use App\Http\Requests\Knowledge\IndexArticlesRequest;
use App\Http\Requests\Knowledge\SaveArticleRequest;
use App\Http\Resources\KbArticleResource;
use App\Http\Resources\KbChunkResource;
use App\Models\AiSuggestion;
use App\Models\KbArticle;
use App\Models\KbChunk;
use App\Services\Ai\Embedder;
use App\Services\Knowledge\MarkdownChunker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Knowledge base', 'Markdown articles the AI may cite (staff only). Only published, indexed articles are searched; drafts never are.')]
final class KnowledgeArticleController extends Controller
{
    /**
     * List articles
     */
    #[ResponseFromApiResource(KbArticleResource::class, KbArticle::class, collection: true, paginate: 25, with: ['category', 'updatedBy'])]
    public function index(IndexArticlesRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', KbArticle::class);

        $articles = KbArticle::query()
            ->with(['category', 'updatedBy'])
            ->when($request->filled('q'), fn ($q) => $q->whereLike('title', '%'.addcslashes($request->string('q')->toString(), '%_\\').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('index_status'), fn ($q) => $q->where('index_status', $request->string('index_status')->toString()))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->string('category_id')->toString()))
            ->latest('updated_at')
            ->orderBy('id')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return KbArticleResource::collection($articles);
    }

    /**
     * Knowledge-base figures
     */
    #[ScribeResponse(['data' => ['published' => 42, 'chunks' => 386, 'queued' => 2, 'failed' => 1, 'gaps' => 3]])]
    public function stats(): JsonResponse
    {
        Gate::authorize('viewAny', KbArticle::class);

        return response()->json(['data' => [
            'published' => KbArticle::query()->where('status', ArticleStatus::Published)->count(),
            'chunks' => KbChunk::query()->count(),
            'queued' => KbArticle::query()->whereIn('index_status', [IndexStatus::Queued, IndexStatus::Indexing])->count(),
            'failed' => KbArticle::query()->where('index_status', IndexStatus::Failed)->count(),
            'gaps' => AiSuggestion::query()->whereNotNull('gap_reported_at')->count(),
        ]]);
    }

    /**
     * Create an article (draft)
     */
    #[ResponseFromApiResource(KbArticleResource::class, KbArticle::class, 201, with: ['category', 'updatedBy'])]
    public function store(SaveArticleRequest $request, SaveKnowledgeArticle $save): JsonResponse
    {
        Gate::authorize('create', KbArticle::class);

        $article = $save->handle($this->currentUser($request), $request->payload());

        return $this->detailed($article)->response()->setStatusCode(201);
    }

    /**
     * Import a Markdown file (draft)
     *
     * The first "# Heading" becomes the title (or the file name).
     */
    #[ResponseFromApiResource(KbArticleResource::class, KbArticle::class, 201, with: ['category', 'updatedBy'])]
    public function import(ImportArticleRequest $request, SaveKnowledgeArticle $save): JsonResponse
    {
        Gate::authorize('create', KbArticle::class);

        $file = $request->file('file');
        $content = is_object($file) && method_exists($file, 'get') ? (string) $file->get() : '';

        if (! mb_check_encoding($content, 'UTF-8') || trim($content) === '') {
            throw ValidationException::withMessages(['file' => 'The file must be UTF-8 text.']);
        }

        $title = Str::of(is_object($file) && method_exists($file, 'getClientOriginalName') ? (string) $file->getClientOriginalName() : 'Artigo importado')
            ->beforeLast('.')->replace(['-', '_'], ' ')->squish()->limit(200, '')->toString();
        $body = $content;

        if (preg_match('/\A\s*#\s+(.+?)\s*\R/u', $content, $match) === 1) {
            $title = Str::limit(trim($match[1]), 200, '');
            $body = (string) preg_replace('/\A\s*#\s+.+?\R/u', '', $content, 1);
        }

        $article = $save->handle($this->currentUser($request), [
            'title' => Str::length($title) >= 3 ? $title : 'Artigo importado',
            'body' => Str::limit(trim($body), SaveArticleRequest::MAX_BODY, ''),
            'visibility' => ArticleVisibility::Customers->value,
            'tags' => [],
        ]);

        return $this->detailed($article)->response()->setStatusCode(201);
    }

    /**
     * Show an article
     */
    #[ResponseFromApiResource(KbArticleResource::class, KbArticle::class, with: ['category', 'updatedBy'])]
    public function show(KbArticle $article): KbArticleResource
    {
        Gate::authorize('view', $article);

        return $this->detailed($article);
    }

    /**
     * Edit an article
     *
     * Changes the working copy; a published article keeps serving its
     * published version until it is published again.
     */
    #[ResponseFromApiResource(KbArticleResource::class, KbArticle::class, with: ['category', 'updatedBy'])]
    public function update(SaveArticleRequest $request, KbArticle $article, SaveKnowledgeArticle $save): KbArticleResource
    {
        Gate::authorize('update', $article);

        return $this->detailed($save->handle($this->currentUser($request), $request->payload(), $article));
    }

    /**
     * Publish an article
     *
     * Publishes the working copy and queues its indexing.
     */
    #[ResponseFromApiResource(KbArticleResource::class, KbArticle::class, with: ['category', 'updatedBy'])]
    public function publish(Request $request, KbArticle $article, PublishKnowledgeArticle $publish): KbArticleResource
    {
        Gate::authorize('update', $article);

        return $this->detailed($publish->handle($this->currentUser($request), $article));
    }

    /**
     * Unpublish an article
     *
     * The AI stops citing it immediately.
     */
    #[ResponseFromApiResource(KbArticleResource::class, KbArticle::class, with: ['category', 'updatedBy'])]
    public function unpublish(Request $request, KbArticle $article, UnpublishKnowledgeArticle $unpublish): KbArticleResource
    {
        Gate::authorize('update', $article);

        return $this->detailed($unpublish->handle($this->currentUser($request), $article));
    }

    /**
     * Index again
     */
    #[ResponseFromApiResource(KbArticleResource::class, KbArticle::class, with: ['category', 'updatedBy'])]
    public function reindex(KbArticle $article, ReindexKnowledgeArticle $reindex): KbArticleResource
    {
        Gate::authorize('update', $article);

        if ($article->status !== ArticleStatus::Published) {
            throw ValidationException::withMessages(['article' => 'Only published articles are indexed.']);
        }

        return $this->detailed($reindex->handle($article));
    }

    /**
     * Indexed passages ("how the AI sees it")
     */
    #[ScribeResponse(['data' => [['id' => '01J...', 'ordinal' => 1, 'heading' => 'Prazos por região', 'content' => 'Sul e Sudeste: 3 a 5 dias úteis.', 'tokens' => 174, 'embedding_model' => 'gemini/gemini-embedding-001', 'citations_count' => 37]], 'meta' => ['embedding_model' => 'gemini/gemini-embedding-001', 'target_tokens' => 320, 'overlap_tokens' => 40]])]
    public function chunks(KbArticle $article, Embedder $embedder): AnonymousResourceCollection
    {
        Gate::authorize('view', $article);

        return KbChunkResource::collection($article->chunks()->orderBy('ordinal')->get())->additional(['meta' => [
            'embedding_model' => $embedder->embeddingModel(),
            'target_tokens' => MarkdownChunker::TARGET_TOKENS,
            'overlap_tokens' => MarkdownChunker::OVERLAP_TOKENS,
        ]]);
    }

    /**
     * Delete an article (admins)
     */
    public function destroy(KbArticle $article): Response
    {
        Gate::authorize('delete', $article);

        $article->delete();

        return response()->noContent();
    }

    private function detailed(KbArticle $article): KbArticleResource
    {
        return KbArticleResource::make($article->load(['category', 'updatedBy']))->withBody();
    }
}
