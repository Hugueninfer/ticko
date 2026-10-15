<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SuggestionStatus;
use App\Models\AiSuggestion;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiSuggestion>
 */
class AiSuggestionFactory extends Factory
{
    /**
     * @return array<model-property<AiSuggestion>, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'requested_by_id' => User::factory(),
            'status' => SuggestionStatus::Ready,
            'body' => 'Olá! O prazo é de até 2 dias úteis [1].',
            'confidence' => '0.850',
            'citations' => [],
            'candidates' => [],
            'provider' => 'fake',
            'model' => 'fake',
        ];
    }
}
