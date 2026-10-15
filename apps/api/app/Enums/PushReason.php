<?php

declare(strict_types=1);

namespace App\Enums;

/** Why a ticket's requester gets a push notification. */
enum PushReason: string
{
    case Reply = 'reply';
    case StatusChange = 'status';
}
