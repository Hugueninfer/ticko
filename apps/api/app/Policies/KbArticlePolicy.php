<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KbArticle;
use App\Models\User;

/** The knowledge base is staff-only; any agent may write, only admins delete. */
final class KbArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, KbArticle $article): bool
    {
        return $user->isStaff() && $user->tenant_id === $article->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, KbArticle $article): bool
    {
        return $this->view($user, $article);
    }

    public function delete(User $user, KbArticle $article): bool
    {
        return $user->isAdmin() && $user->tenant_id === $article->tenant_id;
    }
}
