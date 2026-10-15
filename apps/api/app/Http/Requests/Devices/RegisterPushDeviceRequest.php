<?php

declare(strict_types=1);

namespace App\Http\Requests\Devices;

use App\Enums\PushPlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegisterPushDeviceRequest extends FormRequest
{
    /** Languages the push notifications are written in. */
    public const array LOCALES = ['pt-BR', 'en'];

    /**
     * Any signed-in user may register their own device.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            // Expo push tokens: "ExponentPushToken[...]" (or the newer "ExpoPushToken[...]").
            'token' => ['required', 'string', 'max:255', 'regex:/^Expo(nent)?PushToken\[[A-Za-z0-9_\-]{1,200}\]$/'],
            'platform' => ['required', Rule::enum(PushPlatform::class)],
            'locale' => ['required', 'string', Rule::in(self::LOCALES)],
        ];
    }

    public function platform(): PushPlatform
    {
        return PushPlatform::from($this->string('platform')->toString());
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return [
            'token' => ['description' => 'Expo push token of the device.', 'example' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]'],
            'platform' => ['description' => 'android or ios.', 'example' => 'android'],
            'locale' => ['description' => 'Language of the notifications: pt-BR or en.', 'example' => 'pt-BR'],
        ];
    }
}
