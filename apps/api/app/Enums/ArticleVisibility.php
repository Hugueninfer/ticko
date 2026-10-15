<?php

declare(strict_types=1);

namespace App\Enums;

/** Who an article is for: customers and staff, or staff only. */
enum ArticleVisibility: string
{
    case Customers = 'customers';
    case Internal = 'internal';

    /** Articles the AI may use to draft replies to customers. */
    public function citableInReplies(): bool
    {
        return $this === self::Customers;
    }
}
