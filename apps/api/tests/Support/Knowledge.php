<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Actions\Knowledge\PublishKnowledgeArticle;
use App\Actions\Knowledge\SaveKnowledgeArticle;
use App\Enums\ArticleVisibility;
use App\Models\KbArticle;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

/** Knowledge-base fixtures: articles published and indexed (the queue is synchronous in tests). */
final class Knowledge
{
    public const string DELIVERY = <<<'MD'
        ## Prazos por região
        Sul e Sudeste: 3 a 5 dias úteis após a confirmação do pagamento. Demais regiões: 5 a 10 dias úteis.

        ## Rastreio sem atualização
        Se o rastreamento não for atualizado por mais de 5 dias úteis, abra uma investigação junto à transportadora. O prazo de resposta é de até 2 dias úteis.
        MD;

    public const string RETURNS = <<<'MD'
        ## Como devolver
        Para devolver, gere a etiqueta em Meus pedidos. A troca ou devolução pode ser pedida em até 30 dias após o recebimento.
        MD;

    public static function publish(Tenant $tenant, User $author, string $title, string $body, ArticleVisibility $visibility = ArticleVisibility::Customers, ?string $categoryId = null): KbArticle
    {
        return app(TenantContext::class)->run($tenant, function () use ($author, $title, $body, $visibility, $categoryId): KbArticle {
            $article = app(SaveKnowledgeArticle::class)->handle($author, [
                'title' => $title,
                'body' => $body,
                'visibility' => $visibility->value,
                'tags' => [],
                'category_id' => $categoryId,
            ]);

            return app(PublishKnowledgeArticle::class)->handle($author, $article)->refresh();
        });
    }
}
