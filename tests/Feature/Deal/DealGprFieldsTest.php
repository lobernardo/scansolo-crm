<?php

use App\Enums\DealServiceType;
use App\Livewire\DealDetail;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(\Database\Seeders\DatabaseSeeder::class));

// --- Schema ---

it('deals table has GPR columns', function () {
    expect(Schema::hasColumns('deals', [
        'service_type', 'area_m2', 'scheduled_date', 'description',
    ]))->toBeTrue();
});

// --- Enum ---

it('DealServiceType enum has correct values', function () {
    expect(DealServiceType::MapeamentoGpr->value)->toBe('mapeamento_gpr')
        ->and(DealServiceType::InvestigacaoSubsolo->value)->toBe('investigacao_subsolo')
        ->and(DealServiceType::Batimetria->value)->toBe('batimetria')
        ->and(DealServiceType::InspecaoViaria->value)->toBe('inspecao_viaria')
        ->and(DealServiceType::DiagnosticoGeofisico->value)->toBe('diagnostico_geofisico')
        ->and(DealServiceType::SondagemTunel->value)->toBe('sondagem_tunel')
        ->and(DealServiceType::Outro->value)->toBe('outro');
});

it('DealServiceType labels are in Portuguese', function () {
    expect(DealServiceType::MapeamentoGpr->label())->toBe('Mapeamento GPR')
        ->and(DealServiceType::InvestigacaoSubsolo->label())->toBe('Investigação de Subsolo')
        ->and(DealServiceType::SondagemTunel->label())->toBe('Sondagem em Túnel');
});

// --- Model ---

it('Deal model casts service_type to DealServiceType enum', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);

    $deal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'lead_id' => $lead->id,
        'service_type' => DealServiceType::MapeamentoGpr->value,
    ]);

    expect($deal->fresh()->service_type)->toBe(DealServiceType::MapeamentoGpr);
});

it('Deal model stores all GPR fields correctly', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);

    $deal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'lead_id' => $lead->id,
        'service_type' => DealServiceType::Batimetria,
        'area_m2' => 1500.50,
        'scheduled_date' => '2026-09-15',
        'description' => 'Batimetria de represa industrial',
    ]);

    $fresh = $deal->fresh();
    expect($fresh->service_type)->toBe(DealServiceType::Batimetria)
        ->and((float) $fresh->area_m2)->toBe(1500.50)
        ->and($fresh->scheduled_date->format('Y-m-d'))->toBe('2026-09-15')
        ->and($fresh->description)->toBe('Batimetria de represa industrial');
});

// --- Deal Detail view ---

it('deal detail displays GPR fields in view mode', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $deal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'lead_id' => $lead->id,
        'service_type' => DealServiceType::InspecaoViaria,
        'area_m2' => 800.00,
        'scheduled_date' => '2026-10-20',
        'description' => 'Inspeção de via expressa',
    ]);

    Livewire::actingAs($owner)
        ->test(DealDetail::class)
        ->dispatch('openDealDetail', dealId: $deal->id)
        ->assertSee('Inspeção Viária')
        ->assertSee('800,00')
        ->assertSee('20/10/2026')
        ->assertSee('Inspeção de via expressa');
});

// --- Deal Detail edit ---

it('deal detail saves GPR fields on update', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $deal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'lead_id' => $lead->id,
    ]);

    Livewire::actingAs($owner)
        ->test(DealDetail::class)
        ->dispatch('openDealDetail', dealId: $deal->id)
        ->call('startEditing')
        ->set('editForm.service_type', DealServiceType::DiagnosticoGeofisico->value)
        ->set('editForm.area_m2', '2000')
        ->set('editForm.scheduled_date', '2026-11-01')
        ->set('editForm.description', 'Diagnóstico geofísico de área industrial')
        ->call('saveDeal')
        ->assertHasNoErrors();

    $fresh = $deal->fresh();
    expect($fresh->service_type)->toBe(DealServiceType::DiagnosticoGeofisico)
        ->and((float) $fresh->area_m2)->toBe(2000.0)
        ->and($fresh->scheduled_date->format('Y-m-d'))->toBe('2026-11-01')
        ->and($fresh->description)->toBe('Diagnóstico geofísico de área industrial');
});
