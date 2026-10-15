<?php

declare(strict_types=1);

namespace App\Enums;

/** Why an agent discarded an AI draft (feeds the quality funnel). */
enum DiscardReason: string
{
    case WrongInformation = 'wrong_information';
    case Tone = 'tone';
    case OutdatedSource = 'outdated_source';
    case Other = 'other';
}
