<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * A large language model that turns a prompt into text (here: JSON). The
 * model gets no tools and can take no action: its output is only ever a
 * draft that a person reviews.
 */
interface TextGenerator
{
    /**
     * @throws AiUnavailable when the provider fails, times out or is not configured
     */
    public function generate(TextPrompt $prompt): string;

    public function provider(): string;

    public function model(): string;
}
