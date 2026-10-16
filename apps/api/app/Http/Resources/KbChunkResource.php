<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\KbChunk;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An indexed passage, exactly as the AI receives it.
 *
 * @mixin KbChunk
 */
final class KbChunkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ordinal' => $this->ordinal,
            'heading' => $this->heading,
            'content' => $this->content,
            'tokens' => $this->tokens,
            'embedding_model' => $this->embedding_model,
            'citations_count' => $this->citations_count,
        ];
    }
}
