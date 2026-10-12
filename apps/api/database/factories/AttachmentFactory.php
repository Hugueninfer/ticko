<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * @return array<model-property<Attachment>, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'uploader_id' => User::factory(),
            'disk' => 'local',
            'path' => 'tenants/test/'.Str::ulid().'.png',
            'original_name' => 'screenshot.png',
            'mime_type' => 'image/png',
            'size' => 1024,
            'width' => 10,
            'height' => 10,
            'checksum' => hash('sha256', Str::random()),
        ];
    }
}
