<?php

use App\Enums\LeadSegment;
use App\Enums\LeadSource;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(\Database\Seeders\DatabaseSeeder::class));

it('business owner can access leads page', function () {
    $owner = User::factory()->businessOwner()->create();

    $this->actingAs($owner)->get('/leads')->assertSuccessful();
});

it('salesperson can access leads page', function () {
    $sp = User::factory()->salesperson()->create();

    $this->actingAs($sp)->get('/leads')->assertSuccessful();
});

it('guest is redirected to login', function () {
    $this->get('/leads')->assertRedirect('/login');
});

it('lists leads for the current tenant', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'name' => 'Empresa Alpha', 'company' => 'Alpha Ltda']);

    Livewire::actingAs($owner)
        ->test('pages::leads.index')
        ->assertSee('Empresa Alpha')
        ->assertSee('Alpha Ltda');
});

it('does not show leads from another tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $owner1 = User::factory()->businessOwner()->for($tenant1)->create();
    $owner2 = User::factory()->businessOwner()->for($tenant2)->create();

    Lead::factory()->create(['tenant_id' => $tenant1->id, 'user_id' => $owner1->id, 'name' => 'Lead Tenant 1']);
    Lead::factory()->create(['tenant_id' => $tenant2->id, 'user_id' => $owner2->id, 'name' => 'Lead Tenant 2']);

    Livewire::actingAs($owner1)
        ->test('pages::leads.index')
        ->assertSee('Lead Tenant 1')
        ->assertDontSee('Lead Tenant 2');
});

it('filters leads by name search', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'name' => 'João Silva']);
    Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'name' => 'Maria Oliveira']);

    Livewire::actingAs($owner)
        ->test('pages::leads.index')
        ->set('search', 'João')
        ->assertSee('João Silva')
        ->assertDontSee('Maria Oliveira');
});

it('filters leads by company search', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'company' => 'Construtora Beta']);
    Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'company' => 'Mineração Delta']);

    Livewire::actingAs($owner)
        ->test('pages::leads.index')
        ->set('search', 'Beta')
        ->assertSee('Construtora Beta')
        ->assertDontSee('Mineração Delta');
});

it('filters leads by segment', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'name' => 'Lead Civil', 'segment' => LeadSegment::ConstrucaoCivil]);
    Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'name' => 'Lead Minero', 'segment' => LeadSegment::Mineracao]);

    Livewire::actingAs($owner)
        ->test('pages::leads.index')
        ->set('filterSegment', LeadSegment::ConstrucaoCivil->value)
        ->assertSee('Lead Civil')
        ->assertDontSee('Lead Minero');
});

it('filters leads by source', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'name' => 'Lead Site', 'source' => LeadSource::Site]);
    Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'name' => 'Lead Indicação', 'source' => LeadSource::Indicacao]);

    Livewire::actingAs($owner)
        ->test('pages::leads.index')
        ->set('filterSource', LeadSource::Site->value)
        ->assertSee('Lead Site')
        ->assertDontSee('Lead Indicação');
});

it('clears segment filter to show all leads', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'name' => 'Lead Civil', 'segment' => LeadSegment::ConstrucaoCivil]);
    Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'name' => 'Lead Minero', 'segment' => LeadSegment::Mineracao]);

    Livewire::actingAs($owner)
        ->test('pages::leads.index')
        ->set('filterSegment', LeadSegment::ConstrucaoCivil->value)
        ->assertDontSee('Lead Minero')
        ->set('filterSegment', '')
        ->assertSee('Lead Civil')
        ->assertSee('Lead Minero');
});

it('opens create lead modal', function () {
    $owner = User::factory()->businessOwner()->create();

    Livewire::actingAs($owner)
        ->test('pages::leads.index')
        ->call('openCreateLeadModal')
        ->assertSet('showCreateLeadModal', true);
});

it('creates a new lead and deal via modal', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    Livewire::actingAs($owner)
        ->test('pages::leads.index')
        ->call('openCreateLeadModal')
        ->set('form.email', 'novo@example.com')
        ->call('searchLead')
        ->set('form.name', 'Novo Lead')
        ->set('form.company', 'Empresa Nova')
        ->set('form.deal_title', 'Mapeamento GPR')
        ->set('form.deal_value', '15000')
        ->call('createLead')
        ->assertSet('showCreateLeadModal', false)
        ->assertHasNoErrors();

    expect(Lead::where('email', 'novo@example.com')->exists())->toBeTrue();
    expect(Deal::whereHas('lead', fn ($q) => $q->where('email', 'novo@example.com'))->exists())->toBeTrue();
});

it('opens deal detail when lead has an active deal', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
    $activeStage = PipelineStage::where('is_terminal', false)->orderBy('sort_order')->first();
    $deal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'lead_id' => $lead->id,
        'pipeline_stage_id' => $activeStage->id,
    ]);

    Livewire::actingAs($owner)
        ->test('pages::leads.index')
        ->call('openLeadDeal', $lead->id)
        ->assertDispatched('openDealDetail', dealId: $deal->id);
});

it('opens create modal when lead has no active deal', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);

    Livewire::actingAs($owner)
        ->test('pages::leads.index')
        ->call('openLeadDeal', $lead->id)
        ->assertSet('showCreateLeadModal', true)
        ->assertSet('leadFound', true);
});
