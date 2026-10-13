<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\TicketStatus;

it('knows which roles are staff', function (Role $role, bool $staff): void {
    expect($role->isStaff())->toBe($staff);
})->with([
    [Role::Customer, false],
    [Role::Agent, true],
    [Role::Admin, true],
]);

it('treats only closed tickets as final', function (): void {
    expect(collect(TicketStatus::cases())->filter->isFinal()->all())->toBe([3 => TicketStatus::Closed]);
});
