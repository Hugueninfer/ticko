<?php

declare(strict_types=1);

namespace App\Enums;

/** Who set a ticket field: the AI may only fill fields nobody set. */
enum FieldSource: string
{
    case Requester = 'requester';
    case Ai = 'ai';
    case Staff = 'staff';
}
