<?php

declare(strict_types=1);

// Push notifications are seen on lock screens: never include ticket content.
return [
    'reply' => [
        'title' => 'Nova resposta',
        'body' => 'Nova resposta no ticket :ref',
    ],
    'status' => [
        'title' => 'Ticket atualizado',
        'body' => 'O ticket :ref agora está :status',
    ],
    'statuses' => [
        'open' => 'aberto',
        'pending' => 'aguardando você',
        'resolved' => 'resolvido',
        'closed' => 'fechado',
    ],
];
