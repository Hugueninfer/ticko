<?php

declare(strict_types=1);

namespace App\Services\Ai;

enum EmbeddingTask
{
    /** A knowledge-base passage being indexed. */
    case Document;

    /** A question searched against the index. */
    case Query;
}
