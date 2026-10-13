<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Tenant-scoped: create users inside TenantContext::run() (see tests/Pest.php).
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public const string PASSWORD = 'correct-horse-battery-staple';

    protected static ?string $password;

    /**
     * @return array<model-property<User>, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make(self::PASSWORD),
            'remember_token' => Str::random(10),
            'role' => Role::Customer,
            'is_active' => true,
        ];
    }

    public function customer(): static
    {
        return $this->state(['role' => Role::Customer]);
    }

    public function agent(): static
    {
        return $this->state(['role' => Role::Agent]);
    }

    public function admin(): static
    {
        return $this->state(['role' => Role::Admin]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
