<?php

use App\Enums\LeadSegment;
use App\Enums\LeadSource;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LeadService;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(\Database\Seeders\DatabaseSeeder::class));

// --- Schema ---

it('leads table has GPR columns', function () {
    expect(\Illuminate\Support\Facades\Schema::hasColumns('leads', [
        'company', 'city', 'state', 'segment', 'source',
    ]))->toBeTrue();
});

// --- Enums ---

it('LeadSegment enum has correct values', function () {
    expect(LeadSegment::ConstrucaoCivil->value)->toBe('construcao_civil')
        ->and(LeadSegment::Utilities->value)->toBe('utilities')
        ->and(LeadSegment::Mineracao->value)->toBe('mineracao')
        ->and(LeadSegment::Industrial->value)->toBe('industrial')
        ->and(LeadSegment::Outro->value)->toBe('outro');
});

it('LeadSource enum has correct values', function () {
    expect(LeadSource::Whatsapp->value)->toBe('whatsapp')
        ->and(LeadSource::Site->value)->toBe('site')
        ->and(LeadSource::Indicacao->value)->toBe('indicacao')
        ->and(LeadSource::ProspeccaoAtiva->value)->toBe('prospeccao_ativa')
        ->and(LeadSource::Outro->value)->toBe('outro');
});

it('LeadSource labels are in Portuguese', function () {
    expect(LeadSource::Whatsapp->label())->toBe('WhatsApp')
        ->and(LeadSource::Indicacao->label())->toBe('Indicação')
        ->and(LeadSource::ProspeccaoAtiva->label())->toBe('Prospecção Ativa');
});

it('LeadSegment labels are in Portuguese', function () {
    expect(LeadSegment::ConstrucaoCivil->label())->toBe('Construção Civil')
        ->and(LeadSegment::Mineracao->label())->toBe('Mineração');
});

// --- Model ---

it('Lead model casts segment and source to Enums', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    $this->actingAs($owner);

    $lead = Lead::create([
        'user_id' => $owner->id,
        'name' => 'Test Lead',
        'company' => 'Petrobras',
        'email' => 'test@petrobras.com',
        'segment' => LeadSegment::Industrial->value,
        'source' => LeadSource::ProspeccaoAtiva->value,
    ]);

    expect($lead->segment)->toBeInstanceOf(LeadSegment::class)
        ->and($lead->segment)->toBe(LeadSegment::Industrial)
        ->and($lead->source)->toBeInstanceOf(LeadSource::class)
        ->and($lead->source)->toBe(LeadSource::ProspeccaoAtiva);
});

it('Lead model stores all GPR fields correctly', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    $this->actingAs($owner);

    $lead = Lead::create([
        'user_id' => $owner->id,
        'name' => 'Engenheiro Silva',
        'company' => 'Vale S.A.',
        'email' => 'silva@vale.com',
        'phone' => '11999999999',
        'city' => 'Belo Horizonte',
        'state' => 'MG',
        'segment' => LeadSegment::Mineracao,
        'source' => LeadSource::Indicacao,
    ]);

    $fresh = $lead->fresh();
    expect($fresh->company)->toBe('Vale S.A.')
        ->and($fresh->city)->toBe('Belo Horizonte')
        ->and($fresh->state)->toBe('MG')
        ->and($fresh->segment)->toBe(LeadSegment::Mineracao)
        ->and($fresh->source)->toBe(LeadSource::Indicacao);
});

// --- LeadService ---

it('LeadService creates lead with GPR fields', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    $this->actingAs($owner);

    app(LeadService::class)->createWithDeal(
        owner: $owner,
        leadName: 'Construtora ABC',
        leadEmail: 'contato@abc.com',
        leadPhone: null,
        dealTitle: 'Mapeamento GPR',
        dealValue: '45000',
        company: 'Construtora ABC Ltda',
        city: 'São Paulo',
        state: 'SP',
        segment: LeadSegment::ConstrucaoCivil,
        source: LeadSource::Site,
    );

    $lead = Lead::where('email', 'contato@abc.com')->first();
    expect($lead)->not->toBeNull()
        ->and($lead->company)->toBe('Construtora ABC Ltda')
        ->and($lead->city)->toBe('São Paulo')
        ->and($lead->state)->toBe('SP')
        ->and($lead->segment)->toBe(LeadSegment::ConstrucaoCivil)
        ->and($lead->source)->toBe(LeadSource::Site);
});

// --- Kanban form ---

it('kanban create lead form persists GPR fields for new lead', function () {
    $owner = User::factory()->businessOwner()->create();

    Livewire::actingAs($owner)
        ->test('pages::kanban.index')
        ->set('form.email', 'novo@petrobras.com')
        ->call('searchLead')
        ->set('form.name', 'João Engenheiro')
        ->set('form.company', 'Petrobras S.A.')
        ->set('form.phone', '21999999999')
        ->set('form.city', 'Rio de Janeiro')
        ->set('form.state', 'RJ')
        ->set('form.segment', LeadSegment::Industrial->value)
        ->set('form.source', LeadSource::Whatsapp->value)
        ->set('form.deal_title', 'Inspeção de Dutos')
        ->set('form.deal_value', '120000')
        ->call('createLead')
        ->assertHasNoErrors();

    $lead = Lead::where('email', 'novo@petrobras.com')->first();
    expect($lead)->not->toBeNull()
        ->and($lead->company)->toBe('Petrobras S.A.')
        ->and($lead->city)->toBe('Rio de Janeiro')
        ->and($lead->state)->toBe('RJ')
        ->and($lead->segment)->toBe(LeadSegment::Industrial)
        ->and($lead->source)->toBe(LeadSource::Whatsapp);
});

it('kanban create lead requires company for new leads', function () {
    $owner = User::factory()->businessOwner()->create();

    Livewire::actingAs($owner)
        ->test('pages::kanban.index')
        ->set('form.email', 'semempresa@test.com')
        ->call('searchLead')
        ->set('form.name', 'Lead Sem Empresa')
        ->set('form.company', '')
        ->set('form.deal_title', 'Negócio')
        ->set('form.deal_value', '10000')
        ->call('createLead')
        ->assertHasErrors(['form.company']);
});

// --- Deal Detail ---

it('deal detail displays lead GPR fields', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $lead = Lead::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'company' => 'Vale S.A.',
        'city' => 'Belo Horizonte',
        'state' => 'MG',
        'segment' => LeadSegment::Mineracao,
        'source' => LeadSource::Indicacao,
    ]);
    $deal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'lead_id' => $lead->id,
    ]);

    Livewire::actingAs($owner)
        ->test(\App\Livewire\DealDetail::class)
        ->dispatch('openDealDetail', dealId: $deal->id)
        ->assertSee('Vale S.A.')
        ->assertSee('Belo Horizonte')
        ->assertSee('MG')
        ->assertSee('Mineração')
        ->assertSee('Indicação');
});
