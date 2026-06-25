<?php

use App\Models\ApiToken;
use App\Models\Deal;
use App\Models\DealNote;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\User;

beforeEach(fn () => $this->seed(\Database\Seeders\DatabaseSeeder::class));

function dealNoteToken(Tenant $tenant): string
{
    $plaintext = \Illuminate\Support\Str::random(40);
    ApiToken::create([
        'tenant_id' => $tenant->id,
        'name' => 'Test',
        'token' => hash('sha256', $plaintext),
    ]);

    return $plaintext;
}

it('rejects unauthenticated note creation', function () {
    $this->postJson('/api/deals/1/notes', ['content' => 'Test', 'source' => 'manual'])
        ->assertUnauthorized();
});

it('creates a note and returns 201', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $token = dealNoteToken($tenant);

    $stage = PipelineStage::where('slug', 'contatado')->first();
    $deal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'pipeline_stage_id' => $stage->id,
    ]);

    $this->withToken($token)
        ->postJson("/api/deals/{$deal->id}/notes", [
            'content' => 'Cliente confirmou visita técnica para próxima semana.',
            'source' => 'agent',
        ])
        ->assertCreated()
        ->assertJsonStructure(['note_id', 'created_at']);

    $note = DealNote::withoutGlobalScopes()->where('deal_id', $deal->id)->first();
    expect($note->body)->toBe('Cliente confirmou visita técnica para próxima semana.');
});

it('validates required fields', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $token = dealNoteToken($tenant);

    $stage = PipelineStage::where('slug', 'contatado')->first();
    $deal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'pipeline_stage_id' => $stage->id,
    ]);

    $this->withToken($token)
        ->postJson("/api/deals/{$deal->id}/notes", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['content', 'source']);
});

it('validates source is agent or manual', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $token = dealNoteToken($tenant);

    $stage = PipelineStage::where('slug', 'contatado')->first();
    $deal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'pipeline_stage_id' => $stage->id,
    ]);

    $this->withToken($token)
        ->postJson("/api/deals/{$deal->id}/notes", [
            'content' => 'Nota',
            'source' => 'invalido',
        ])
        ->assertUnprocessable();
});

it('returns 404 when deal belongs to different tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $owner1 = User::factory()->businessOwner()->for($tenant1)->create();
    $owner2 = User::factory()->businessOwner()->for($tenant2)->create();
    $token1 = dealNoteToken($tenant1);

    $stage = PipelineStage::where('slug', 'contatado')->first();
    $dealOfTenant2 = Deal::factory()->create([
        'tenant_id' => $tenant2->id,
        'user_id' => $owner2->id,
        'pipeline_stage_id' => $stage->id,
    ]);

    $this->withToken($token1)
        ->postJson("/api/deals/{$dealOfTenant2->id}/notes", [
            'content' => 'Tentativa de acesso cruzado.',
            'source' => 'agent',
        ])
        ->assertNotFound();
});
