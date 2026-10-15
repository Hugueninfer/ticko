<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Notifications\RegisterPushDevice;
use App\Http\Attributes\SelfService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Devices\RegisterPushDeviceRequest;
use App\Models\PersonalAccessToken;
use App\Models\PushDevice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Group;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[Group('Push notifications', 'Devices that receive push notifications (mobile app, bearer tokens only).')]
final class PushDeviceController extends Controller
{
    /**
     * Register this device
     *
     * Saves the Expo push token of the phone using the current API token.
     * Revoking the API token (signing out) unregisters the device too.
     */
    #[SelfService]
    public function store(RegisterPushDeviceRequest $request, RegisterPushDevice $register): Response
    {
        $register->handle(
            $this->currentUser($request),
            $this->accessToken($request),
            $request->string('token')->toString(),
            $request->platform(),
            $request->string('locale')->toString(),
        );

        return response()->noContent();
    }

    /**
     * Unregister this device
     *
     * Stops push notifications to the phone using the current API token.
     */
    #[SelfService]
    public function destroy(Request $request): Response
    {
        PushDevice::query()->where('personal_access_token_id', $this->accessToken($request)->id)->delete();

        return response()->noContent();
    }

    private function accessToken(Request $request): PersonalAccessToken
    {
        $token = $this->currentUser($request)->currentAccessToken();

        if (! $token instanceof PersonalAccessToken || ! $token->exists) {
            throw new BadRequestHttpException('Push devices can only be registered with an API token.');
        }

        return $token;
    }
}
