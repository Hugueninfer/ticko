<?php

declare(strict_types=1);

namespace App\Http\Requests\Tickets;

use App\Actions\Tickets\ListTickets;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexTicketsRequest extends FormRequest
{
    /**
     * Authorized before validation, so validation errors never leak data to callers without access.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Ticket::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $sorts = collect(ListTickets::SORTABLE)->flatMap(fn (string $c): array => [$c, '-'.$c])->all();

        return [
            'status' => ['sometimes', Rule::enum(TicketStatus::class)],
            'state' => ['sometimes', Rule::in(array_keys(ListTickets::STATES))],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'category_id' => ['sometimes', 'ulid'],
            'assignee_id' => ['sometimes', 'ulid'],
            'requester_id' => ['sometimes', 'ulid'],
            'unassigned' => ['sometimes', 'boolean'],
            'overdue' => ['sometimes', 'boolean'],
            'q' => ['sometimes', 'string', 'max:100'],
            'sort' => ['sometimes', Rule::in($sorts)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{status?: string, priority?: string, category_id?: string, assignee_id?: string, requester_id?: string, unassigned?: bool, overdue?: bool, q?: string, sort?: string, per_page?: int}
     */
    public function filters(): array
    {
        /** @var array{status?: string, state?: string, priority?: string, category_id?: string, assignee_id?: string, requester_id?: string, q?: string, sort?: string} $filters */
        $filters = $this->safe()->except(['unassigned', 'overdue', 'per_page', 'page']);

        if ($this->has('unassigned')) {
            $filters['unassigned'] = $this->boolean('unassigned');
        }

        if ($this->has('overdue')) {
            $filters['overdue'] = $this->boolean('overdue');
        }

        if ($this->has('per_page')) {
            $filters['per_page'] = $this->integer('per_page');
        }

        return $filters;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'status' => ['description' => 'Filter by status.', 'example' => 'open'],
            'state' => ['description' => 'Filter by group of statuses: active (open, pending) or done (resolved, closed).', 'example' => 'active'],
            'priority' => ['description' => 'Filter by priority.', 'example' => 'high'],
            'category_id' => ['description' => 'Filter by category ULID.', 'example' => ''],
            'assignee_id' => ['description' => 'Filter by assignee ULID.', 'example' => ''],
            'requester_id' => ['description' => 'Filter by requester ULID (staff only).', 'example' => ''],
            'unassigned' => ['description' => 'Only tickets without an assignee.', 'example' => ''],
            'overdue' => ['description' => 'Only unresolved tickets with a missed SLA deadline.', 'example' => ''],
            'q' => ['description' => 'Search subject and description.', 'example' => 'printer'],
            'sort' => ['description' => 'Sort field; prefix with - for descending.', 'example' => '-created_at'],
            'per_page' => ['description' => 'Page size (1-100).', 'example' => '25'],
            'page' => ['description' => 'Page number.', 'example' => '1'],
        ];
    }
}
