<?php

use App\Enums\DealServiceType;
use App\Enums\LeadSource;
use App\Models\ApiToken;
use App\Models\Deal;
use App\Models\DealNote;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;

beforeEach(fn () => $this->seed(\Database\Seeders\DatabaseSeeder::class));

function makeToken(Tenant $tenant): string
{
    $plaintext = \Illuminate\Support\Str::random(40);
    ApiToken::create([
        'tenant_id' => $tenant->id,
        'name' => 'Test Token',
        'token' => hash('sha256', $plaintext),
    ]);

    return $plaintext;
}

// --- Authentication ---

it('rejects requests without bearer token', function () {
    $this->postJson('/api/leads/inbound', ['name' => 'Test', 'company' => 'Test Co'])
        ->assertUnauthorized();
});

it('rejects requests with invalid bearer token', function () {
    $this->withToken('invalid-token')
        ->postJson('/api/leads/inbound', ['name' => 'Test', 'company' => 'Test Co'])
        ->assertUnauthorized();
});

// --- Validation ---

it('validates required fields', function () {
    $tenant = Tenant::factory()->create();
    User::factory()->businessOwner()->for($tenant)->create();
    $token = makeToken($tenant);

    $this->withToken($token)
        ->postJson('/api/leads/inbound', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'company']);
});

// --- Happy path ---

it('creates a new lead and deal and returns 201', function () {
    $tenant = Tenant::factory()->create();
    User::factory()->businessOwner()->for($tenant)->create();
    $token = makeToken($tenant);

    $response = $this->withToken($token)->postJson('/api/leads/inbound', [
        'name' => 'João Silva',
        'company' => 'Construtora Beta',
        'email' => 'joao@beta.com',
        'phone' => '11999999999',
        'source' => LeadSource::Site->value,
        'deal_title' => 'Mapeamento GPR - Obra Central',
        'deal_area_m2' => 1500.5,
        'deal_service_type' => DealServiceType::MapeamentoGpr->value,
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['lead_id', 'deal_id', 'stage'])
        ->assertJson(['stage' => 'contatado']);

    expect(Lead::withoutGlobalScopes()->where('email', 'joao@beta.com')->exists())->toBeTrue();
    expect(Deal::withoutGlobalScopes()->where('title', 'Mapeamento GPR - Obra Central')->exists())->toBeTrue();
});

it('places the deal in the contatado stage', function () {
    $tenant = Tenant::factory()->create();
    User::factory()->businessOwner()->for($tenant)->create();
    $token = makeToken($tenant);

    $response = $this->withToken($token)->postJson('/api/leads/inbound', [
        'name' => 'Maria',
        'company' => 'Engenharia X',
    ]);

    $response->assertCreated();

    $deal = Deal::withoutGlobalScopes()->find($response->json('deal_id'));
    expect($deal->pipelineStage->slug)->toBe('contatado');
});

it('creates automatic note with source info', function () {
    $tenant = Tenant::factory()->create();
    User::factory()->businessOwner()->for($tenant)->create();
    $token = makeToken($tenant);

    $response = $this->withToken($token)->postJson('/api/leads/inbound', [
        'name' => 'Carlos',
        'company' => 'Empresa Y',
        'source' => LeadSource::Whatsapp->value,
    ]);

    $response->assertCreated();
    $dealId = $response->json('deal_id');

    $note = DealNote::withoutGlobalScopes()->where('deal_id', $dealId)->first();
    expect($note)->not->toBeNull();
    expect($note->body)->toContain('WhatsApp');
});

it('uses default deal title when not provided', function () {
    $tenant = Tenant::factory()->create();
    User::factory()->businessOwner()->for($tenant)->create();
    $token = makeToken($tenant);

    $this->withToken($token)->postJson('/api/leads/inbound', [
        'name' => 'Pedro',
        'company' => 'Mineração Leste',
    ])->assertCreated();

    expect(Deal::withoutGlobalScopes()->where('title', 'Serviço GPR - Mineração Leste')->exists())->toBeTrue();
});

it('reuses existing lead with same email and creates new deal', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $token = makeToken($tenant);

    $existingLead = Lead::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'email' => 'existente@empresa.com',
    ]);

    $response = $this->withToken($token)->postJson('/api/leads/inbound', [
        'name' => 'Existente',
        'company' => 'Empresa',
        'email' => 'existente@empresa.com',
        'deal_title' => 'Novo Projeto',
    ]);

    $response->assertCreated();
    expect($response->json('lead_id'))->toBe($existingLead->id);
    expect(Deal::withoutGlobalScopes()->where('lead_id', $existingLead->id)->count())->toBe(1);
});

// --- Tenant isolation ---

it('does not allow using a token from another tenant to create lead in different tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    User::factory()->businessOwner()->for($tenant1)->create();
    User::factory()->businessOwner()->for($tenant2)->create();
    $token1 = makeToken($tenant1);

    $this->withToken($token1)->postJson('/api/leads/inbound', [
        'name' => 'Test',
        'company' => 'Test Co',
    ])->assertCreated();

    $leads = Lead::withoutGlobalScopes()->latest()->first();
    expect($leads->tenant_id)->toBe($tenant1->id);
});
