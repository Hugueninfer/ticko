<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClassificationStatus;
use App\Enums\Sentiment;
use App\Enums\TicketPriority;
use App\Models\Ticket;
use App\Models\TicketClassification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketClassification>
 */
class TicketClassificationFactory extends Factory
{
    /**
     * @return array<model-property<TicketClassification>, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'status' => ClassificationStatus::Classified,
            'model' => 'bertimbau-lora-v1',
            'category_label' => 'entrega',
            'category_confidence' => '0.930',
            'priority' => TicketPriority::High,
            'priority_confidence' => '0.810',
            'sentiment' => Sentiment::Negative,
            'sentiment_confidence' => '0.880',
            'applied' => ['category', 'priority', 'sentiment'],
            'flags' => [],
        ];
    }
}
