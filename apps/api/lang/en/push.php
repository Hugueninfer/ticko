<?php

declare(strict_types=1);

// Push notifications are seen on lock screens: never include ticket content.
return [
    'reply' => [
        'title' => 'New reply',
        'body' => 'New reply on ticket :ref',
    ],
    'status' => [
        'title' => 'Ticket updated',
        'body' => 'Ticket :ref is now :status',
    ],
    'statuses' => [
        'open' => 'open',
        'pending' => 'waiting for you',
        'resolved' => 'resolved',
        'closed' => 'closed',
    ],
];
