<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PushPlatform;
use App\Models\PushDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PushDevice>
 */
class PushDeviceFactory extends Factory
{
    /**
     * Requires a personal_access_token_id: pass the id of one of the user's tokens.
     *
     * @return array<model-property<PushDevice>, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => 'ExponentPushToken['.Str::random(22).']',
            'platform' => PushPlatform::Android,
            'locale' => 'pt-BR',
        ];
    }
}
