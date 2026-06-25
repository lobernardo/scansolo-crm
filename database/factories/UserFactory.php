<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'tenant_id' => Tenant::factory(),
            'role_id' => fn () => Role::where('name', UserRole::BusinessOwner->value)->first()?->id ?? Role::factory()->businessOwner()->create()->id,
            'user_status_id' => fn () => UserStatus::where('name', AccountStatus::Active->value)->first()?->id ?? UserStatus::factory()->active()->create()->id,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function businessOwner(): static
    {
        return $this->state(fn () => [
            'role_id' => fn () => Role::where('name', UserRole::BusinessOwner->value)->first()?->id ?? Role::factory()->businessOwner()->create()->id,
        ]);
    }

    public function salesperson(): static
    {
        return $this->state(fn () => [
            'role_id' => fn () => Role::where('name', UserRole::Salesperson->value)->first()?->id ?? Role::factory()->salesperson()->create()->id,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'user_status_id' => fn () => UserStatus::where('name', AccountStatus::Active->value)->first()?->id ?? UserStatus::factory()->active()->create()->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'user_status_id' => fn () => UserStatus::where('name', AccountStatus::Inactive->value)->first()?->id ?? UserStatus::factory()->inactive()->create()->id,
        ]);
    }
}
