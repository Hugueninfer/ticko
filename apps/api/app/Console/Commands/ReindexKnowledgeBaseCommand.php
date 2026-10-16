<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Knowledge\ReindexKnowledgeArticle;
use App\Enums\ArticleStatus;
use App\Models\KbArticle;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;

/** Queues the indexing of every published article, e.g. after changing the embedding provider. */
final class ReindexKnowledgeBaseCommand extends Command
{
    protected $signature = 'ticko:kb:reindex {--tenant= : Only this tenant (slug)}';

    protected $description = 'Index every published knowledge-base article again (after changing the embedding provider)';

    public function handle(TenantContext $tenants, ReindexKnowledgeArticle $reindex): int
    {
        $slug = $this->option('tenant');
        $query = Tenant::query()->when(is_string($slug), fn ($q) => $q->where('slug', $slug));
        $total = 0;

        foreach ($query->get() as $tenant) {
            $total += $tenants->run($tenant, function () use ($reindex): int {
                $articles = KbArticle::query()->where('status', ArticleStatus::Published)->get();
                $articles->each(fn (KbArticle $article) => $reindex->handle($article));

                return $articles->count();
            });
        }

        $this->info("Queued {$total} articles for indexing.");

        return self::SUCCESS;
    }
}
