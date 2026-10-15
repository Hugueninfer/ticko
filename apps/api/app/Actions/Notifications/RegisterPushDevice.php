<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Enums\PushPlatform;
use App\Models\PersonalAccessToken;
use App\Models\PushDevice;
use App\Models\User;

final readonly class RegisterPushDevice
{
    /**
     * Registers (or moves) a device to the user and access token that sent it.
     * A phone handed to another account of the tenant stops receiving the
     * previous account's notifications.
     */
    public function handle(User $user, PersonalAccessToken $accessToken, string $token, PushPlatform $platform, string $locale): PushDevice
    {
        $device = PushDevice::query()->firstOrNew(['token' => $token]);
        $device->user_id = $user->id;
        $device->personal_access_token_id = $accessToken->id;
        $device->platform = $platform;
        $device->locale = $locale;
        $device->save();

        return $device;
    }
}
