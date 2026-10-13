<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Team\CreateTeamMember;
use App\Actions\Team\UpdateTeamMember;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\IndexTeamRequest;
use App\Http\Requests\Team\StoreTeamMemberRequest;
use App\Http\Requests\Team\UpdateTeamMemberRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Team', 'Members of the tenant. Staff can list them; only admins create or change them.')]
final class TeamController extends Controller
{
    /**
     * List members
     */
    #[ResponseFromApiResource(UserResource::class, User::class, collection: true, paginate: 50)]
    public function index(IndexTeamRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $role = $request->role();
        $search = $request->search();
        $members = User::query()
            ->withCount(['assignedTickets as open_tickets_count' => fn (Builder $q) => $q->whereIn('status', [TicketStatus::Open, TicketStatus::Pending])])
            ->when($role !== null, fn (Builder $q) => $q->where('role', $role))
            ->when($search !== null, fn (Builder $q) => $q->where(fn (Builder $q) => $q->whereLike('name', (string) $search)->orWhereLike('email', (string) $search)))
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return UserResource::collection($members);
    }

    /**
     * Add a member
     */
    #[ResponseFromApiResource(UserResource::class, User::class, 201)]
    public function store(StoreTeamMemberRequest $request, CreateTeamMember $create): UserResource
    {
        Gate::authorize('create', User::class);

        return UserResource::make($create->handle($request->payload()));
    }

    /**
     * Show a member
     */
    #[ResponseFromApiResource(UserResource::class, User::class)]
    public function show(User $member): UserResource
    {
        Gate::authorize('view', $member);

        return UserResource::make($member);
    }

    /**
     * Update a member
     *
     * Admins cannot change their own role or deactivate themselves.
     */
    #[ResponseFromApiResource(UserResource::class, User::class)]
    public function update(UpdateTeamMemberRequest $request, User $member, UpdateTeamMember $update): UserResource
    {
        Gate::authorize('update', $member);

        return UserResource::make($update->handle($member, $request->payload()));
    }
}
