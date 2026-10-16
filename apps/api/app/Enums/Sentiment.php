<?php

declare(strict_types=1);

namespace App\Enums;

/** The customer's mood in a ticket, as read by the classifier or set by an agent. */
enum Sentiment: string
{
    case Negative = 'negative';
    case Neutral = 'neutral';
    case Positive = 'positive';
}
