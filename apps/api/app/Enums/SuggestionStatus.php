<?php

declare(strict_types=1);

namespace App\Enums;

/** Lifecycle of an AI reply draft; only a person can approve or discard it. */
enum SuggestionStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Insufficient = 'insufficient';
    case Failed = 'failed';
    case Approved = 'approved';
    case Discarded = 'discarded';

    /** A person already decided what to do with it. */
    public function isReviewed(): bool
    {
        return $this === self::Approved || $this === self::Discarded;
    }
}
