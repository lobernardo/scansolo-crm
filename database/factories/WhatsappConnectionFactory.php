<?php

namespace Database\Factories;

use App\Enums\ConnectionStatus;
use App\Models\Tenant;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WhatsappConnection> */
class WhatsappConnectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'whatsapp_connection_status_id' => fn () => WhatsappConnectionStatus::where('name', ConnectionStatus::Disconnected->value)->first()?->id ?? WhatsappConnectionStatus::factory()->create(['name' => ConnectionStatus::Disconnected->value])->id,
            'instance_name' => null,
            'instance_id' => null,
            'phone_number' => null,
        ];
    }

    public function connected(): static
    {
        return $this->state(fn () => [
            'whatsapp_connection_status_id' => fn () => WhatsappConnectionStatus::where('name', ConnectionStatus::Connected->value)->first()?->id ?? WhatsappConnectionStatus::factory()->create(['name' => ConnectionStatus::Connected->value])->id,
            'instance_name' => fake()->slug(2),
            'instance_id' => fake()->uuid(),
            'phone_number' => fake()->phoneNumber(),
        ]);
    }
}
