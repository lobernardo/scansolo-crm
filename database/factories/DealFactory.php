<?php

namespace Database\Factories;

use App\Enums\DealServiceType;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Deal> */
class DealFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'lead_id' => Lead::factory(),
            'user_id' => User::factory(),
            'pipeline_stage_id' => fn () => PipelineStage::where('is_terminal', false)->orderBy('sort_order')->first()?->id ?? PipelineStage::factory()->create()->id,
            'title' => fake()->sentence(3),
            'value' => fake()->randomFloat(2, 45000, 520000),
            'service_type' => fake()->randomElement(DealServiceType::cases()),
            'area_m2' => fake()->optional(0.6)->randomFloat(2, 100, 5000),
            'scheduled_date' => fake()->optional(0.5)->dateTimeBetween('now', '+6 months')?->format('Y-m-d'),
            'description' => fake()->optional(0.4)->sentence(10),
            'loss_reason' => null,
            'sort_order' => 0,
        ];
    }

    public function won(): static
    {
        return $this->state(fn () => [
            'pipeline_stage_id' => fn () => PipelineStage::where('is_won', true)->first()?->id ?? PipelineStage::factory()->won()->create(['name' => 'Won'])->id,
        ]);
    }

    public function lost(string $reason = 'Cliente desistiu'): static
    {
        return $this->state(fn () => [
            'pipeline_stage_id' => fn () => PipelineStage::where('is_terminal', true)->where('is_won', false)->first()?->id ?? PipelineStage::factory()->terminal()->create(['name' => 'Lost'])->id,
            'loss_reason' => $reason,
        ]);
    }
}
