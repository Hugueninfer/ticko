<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Ticket;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->admin = $this->userIn($this->tenant, Role::Admin);
});

it('lets every role list and view categories', function (Role $role): void {
    $category = $this->inTenant($this->tenant, fn () => Category::factory()->create(['name' => 'Hardware']));
    $user = $this->userIn($this->tenant, $role);

    $this->actingAsUser($user)->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Hardware');
    $this->getJson("/api/v1/categories/{$category->id}")->assertOk()->assertJsonPath('data.slug', 'hardware');
})->with([Role::Customer, Role::Agent, Role::Admin]);

it('lets admins create, rename and delete categories', function (): void {
    $this->actingAsUser($this->admin);

    $id = $this->postJson('/api/v1/categories', ['name' => 'Network Access', 'description' => 'VPN and Wi-Fi'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'network-access')
        ->json('data.id');

    $this->patchJson("/api/v1/categories/{$id}", ['name' => 'Networking'])
        ->assertOk()
        ->assertJsonPath('data.slug', 'networking')
        ->assertJsonPath('data.description', 'VPN and Wi-Fi');

    $ticket = $this->inTenant($this->tenant, fn () => Ticket::factory()->create([
        'requester_id' => $this->admin->id, 'category_id' => $id,
    ]));

    $this->deleteJson("/api/v1/categories/{$id}")->assertNoContent();

    $this->inTenant($this->tenant, function () use ($ticket, $id): void {
        expect(Category::find($id))->toBeNull()
            ->and($ticket->fresh()->category_id)->toBeNull();
    });
});

it('keeps category names unique per tenant only', function (): void {
    $this->inTenant($this->tenant, fn () => Category::factory()->create(['name' => 'Hardware', 'slug' => 'hardware']));
    $other = Tenant::factory()->create();
    $otherAdmin = $this->userIn($other, Role::Admin);

    $this->actingAsUser($this->admin)
        ->postJson('/api/v1/categories', ['name' => 'hardware'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    $this->actingAsUser($otherAdmin)->postJson('/api/v1/categories', ['name' => 'Hardware'])->assertCreated();
});

it('lets an admin save a category without changing its name', function (): void {
    $category = $this->inTenant($this->tenant, fn () => Category::factory()->create(['name' => 'Hardware', 'slug' => 'hardware']));

    $this->actingAsUser($this->admin)
        ->putJson("/api/v1/categories/{$category->id}", ['name' => 'Hardware', 'description' => null])
        ->assertOk()
        ->assertJsonPath('data.description', null);
});

it('forbids non-admins from managing categories', function (Role $role): void {
    $category = $this->inTenant($this->tenant, fn () => Category::factory()->create());
    $this->actingAsUser($this->userIn($this->tenant, $role));

    $this->postJson('/api/v1/categories', ['name' => 'Nope'])->assertForbidden();
    $this->patchJson("/api/v1/categories/{$category->id}", ['name' => 'Nope'])->assertForbidden();
    $this->deleteJson("/api/v1/categories/{$category->id}")->assertForbidden();
})->with([Role::Customer, Role::Agent]);
