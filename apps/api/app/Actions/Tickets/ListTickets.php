<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class ListTickets
{
    public const array SORTABLE = ['created_at', 'updated_at', 'priority', 'status', 'first_response_due_at', 'resolution_due_at'];

    /**
     * @param  array{status?: string, priority?: string, category_id?: string, assignee_id?: string, requester_id?: string, unassigned?: bool, overdue?: bool, q?: string, sort?: string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, Ticket>
     */
    public function handle(User $viewer, array $filters): LengthAwarePaginator
    {
        $query = Ticket::query()->with(['requester', 'assignee', 'category']);

        // Customers only ever see the tickets they opened.
        if (! $viewer->isStaff()) {
            $query->where('requester_id', $viewer->id);
        } elseif (isset($filters['requester_id'])) {
            $query->where('requester_id', $filters['requester_id']);
        }

        foreach (['status', 'priority', 'category_id', 'assignee_id'] as $column) {
            if (isset($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }

        if (($filters['unassigned'] ?? false) === true) {
            $query->whereNull('assignee_id');
        }

        if (($filters['overdue'] ?? false) === true) {
            $query->overdue();
        }

        if (isset($filters['q']) && $filters['q'] !== '') {
            $term = '%'.addcslashes($filters['q'], '%_\\').'%';
            $query->where(fn (Builder $q) => $q->whereLike('subject', $term)->orWhereLike('description', $term));
        }

        $sort = $filters['sort'] ?? '-created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if ($column === 'priority') {
            // Order by severity, not alphabetically.
            $levels = array_map(fn (TicketPriority $p): string => $p->value, TicketPriority::cases());
            $query->orderByRaw('array_position(?::text[], priority::text) '.$direction, ['{'.implode(',', $levels).'}']);
        } else {
            $query->orderBy($column, $direction);
        }

        return $query
            ->orderBy('id', $direction)
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();
    }
}
