<?php

namespace Database\Factories;

use App\Enums\LeadSegment;
use App\Enums\LeadSource;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lead> */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'company' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(['SP', 'RJ', 'MG', 'RS', 'PR', 'BA', 'GO']),
            'segment' => fake()->randomElement(LeadSegment::cases()),
            'source' => fake()->randomElement(LeadSource::cases()),
        ];
    }
}
