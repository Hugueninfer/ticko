<?php

declare(strict_types=1);

namespace App\Http\Requests\Knowledge;

use App\Models\KbArticle;
use Illuminate\Foundation\Http\FormRequest;

final class ImportArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', KbArticle::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            // Plain text only, at most 200 KB; imported as a draft to review before publishing.
            'file' => ['required', 'file', 'max:200', 'extensions:md,markdown,txt', 'mimetypes:text/plain,text/markdown,text/x-markdown'],
        ];
    }
}
