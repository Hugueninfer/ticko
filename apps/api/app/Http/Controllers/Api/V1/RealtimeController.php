<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Attributes\SelfService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;

#[Group('Realtime', 'WebSocket (Pusher protocol, Laravel Reverb) on the API origin under /app. Subscribe to private channels after authorizing them with POST /api/broadcasting/auth.')]
final class RealtimeController extends Controller
{
    /**
     * Realtime settings
     *
     * Public app key of the WebSocket server, so clients need no build-time
     * configuration. The key is not a secret; channel access is authorized
     * per subscription.
     */
    #[SelfService]
    #[ScribeResponse(['data' => ['enabled' => true, 'key' => 'ticko-app-key']])]
    public function show(): JsonResponse
    {
        $enabled = config('broadcasting.default') === 'reverb';
        $key = config('broadcasting.connections.reverb.key');

        return response()->json(['data' => [
            'enabled' => $enabled && is_string($key) && $key !== '',
            'key' => $enabled && is_string($key) ? $key : null,
        ]]);
    }
}
