<?php

declare(strict_types=1);

namespace App\Http\Requests\Suggestions;

use App\Models\AiSuggestion;
use Illuminate\Foundation\Http\FormRequest;

final class ApproveSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $suggestion = $this->route('suggestion');

        return $suggestion instanceof AiSuggestion && ($this->user()?->can('review', $suggestion) ?? false);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:20000']];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return ['body' => ['description' => 'The reply as the agent wants to send it (the draft, possibly edited).', 'example' => 'Oi, Mariana! O prazo é de até 2 dias úteis.']];
    }
}
