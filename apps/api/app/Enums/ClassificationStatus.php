<?php

declare(strict_types=1);

namespace App\Enums;

enum ClassificationStatus: string
{
    case Classified = 'classified';
    case Failed = 'failed';
}
