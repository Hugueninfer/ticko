<?php

declare(strict_types=1);

namespace App\Enums;

/** Where the published version of an article is in the indexing pipeline. */
enum IndexStatus: string
{
    case None = 'none';
    case Queued = 'queued';
    case Indexing = 'indexing';
    case Indexed = 'indexed';
    case Failed = 'failed';
}
