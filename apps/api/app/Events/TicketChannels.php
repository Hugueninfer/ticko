<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;

/**
 * Names of the private realtime channels; authorized in routes/channels.php.
 *
 * Events only say that something changed (ids, never content): clients then
 * load the data through the API, which applies the usual authorization.
 */
final class TicketChannels
{
    /** One ticket: its requester and the tenant's staff. */
    public static function ticket(string $tenantId, string $ticketId): PrivateChannel
    {
        return new PrivateChannel("tenant.{$tenantId}.tickets.{$ticketId}");
    }

    /** Every ticket of the tenant (inbox updates): staff only. */
    public static function staff(string $tenantId): PrivateChannel
    {
        return new PrivateChannel("tenant.{$tenantId}.staff");
    }
}
