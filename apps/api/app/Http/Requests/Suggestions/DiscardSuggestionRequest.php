<?php

declare(strict_types=1);

namespace App\Http\Requests\Suggestions;

use App\Enums\DiscardReason;
use App\Models\AiSuggestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DiscardSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $suggestion = $this->route('suggestion');

        return $suggestion instanceof AiSuggestion && ($this->user()?->can('review', $suggestion) ?? false);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['reason' => ['required', Rule::enum(DiscardReason::class)]];
    }

    public function reason(): DiscardReason
    {
        return DiscardReason::from($this->string('reason')->toString());
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return ['reason' => ['description' => 'wrong_information, tone, outdated_source or other.', 'example' => 'tone']];
    }
}
