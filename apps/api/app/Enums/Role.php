<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Customer = 'customer';
    case Agent = 'agent';
    case Admin = 'admin';

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }
}
