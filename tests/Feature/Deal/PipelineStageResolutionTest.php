<?php

use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DealService;
use App\Services\LeadService;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(\Database\Seeders\DatabaseSeeder::class));

// --- is_won flag ---

it('pipeline stages have correct is_won flags after seeding', function () {
    expect(PipelineStage::where('is_won', true)->count())->toBe(1);
    expect(PipelineStage::where('is_terminal', true)->where('is_won', false)->count())->toBe(1);
    expect(PipelineStage::where('is_terminal', false)->count())->toBe(5);
});

it('won stage has is_terminal and is_won both true', function () {
    $wonStage = PipelineStage::where('is_won', true)->first();

    expect($wonStage)->not->toBeNull()
        ->and($wonStage->is_terminal)->toBeTrue()
        ->and($wonStage->is_won)->toBeTrue();
});

it('lost stage has is_terminal true and is_won false', function () {
    $lostStage = PipelineStage::where('is_terminal', true)->where('is_won', false)->first();

    expect($lostStage)->not->toBeNull()
        ->and($lostStage->is_terminal)->toBeTrue()
        ->and($lostStage->is_won)->toBeFalse();
});

// --- DealService ---

it('markAsWon moves deal to the won stage', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $deal = Deal::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'lead_id' => $lead->id]);

    app(DealService::class)->markAsWon($deal);

    expect($deal->fresh()->pipelineStage->is_won)->toBeTrue();
});

it('markAsLost moves deal to the lost stage and saves reason', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $deal = Deal::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'lead_id' => $lead->id]);

    app(DealService::class)->markAsLost($deal, 'Cliente optou pela concorrência');

    $fresh = $deal->fresh();
    expect($fresh->pipelineStage->is_terminal)->toBeTrue()
        ->and($fresh->pipelineStage->is_won)->toBeFalse()
        ->and($fresh->loss_reason)->toBe('Cliente optou pela concorrência');
});

it('requiresLossReason returns true only for the lost stage', function () {
    $service = app(DealService::class);
    $wonStage = PipelineStage::where('is_won', true)->first();
    $lostStage = PipelineStage::where('is_terminal', true)->where('is_won', false)->first();
    $activeStage = PipelineStage::where('is_terminal', false)->orderBy('sort_order')->first();

    expect($service->requiresLossReason($lostStage->id))->toBeTrue()
        ->and($service->requiresLossReason($wonStage->id))->toBeFalse()
        ->and($service->requiresLossReason($activeStage->id))->toBeFalse();
});

// --- LeadService ---

it('creating a deal places it in the first active stage', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    $this->actingAs($owner);

    $deal = app(LeadService::class)->createWithDeal(
        owner: $owner,
        leadName: 'Empresa Teste',
        leadEmail: 'contato@empresa.com',
        leadPhone: null,
        dealTitle: 'Escaneamento GPR',
        dealValue: '15000',
    );

    $firstStage = PipelineStage::where('is_terminal', false)->orderBy('sort_order')->first();
    expect($deal->pipeline_stage_id)->toBe($firstStage->id);
});

// --- Dashboard resilience (proves stage renaming won't break metrics) ---

it('dashboard won metrics work regardless of stage name', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);

    Deal::factory()->won()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'lead_id' => $lead->id, 'value' => 45000]);
    Deal::factory()->won()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'lead_id' => $lead->id, 'value' => 30000]);

    PipelineStage::where('is_won', true)->first()->update(['name' => 'Ganho']);

    $component = Livewire::actingAs($owner)->test('pages::dashboard.index');
    expect($component->get('wonDealsCount'))->toBe(2)
        ->and($component->get('wonDealsValue'))->toBe(75000.0);
});

it('dashboard lost metrics work regardless of stage name', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);

    Deal::factory()->lost('Motivo A')->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'lead_id' => $lead->id]);
    Deal::factory()->lost('Motivo B')->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'lead_id' => $lead->id]);

    PipelineStage::where('is_terminal', true)->where('is_won', false)->first()->update(['name' => 'Perdido']);

    $component = Livewire::actingAs($owner)->test('pages::dashboard.index');
    expect($component->get('lostDealsCount'))->toBe(2);
});
