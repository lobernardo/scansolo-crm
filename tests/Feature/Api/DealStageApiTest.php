<?php

use App\Models\ApiToken;
use App\Models\Deal;
use App\Models\DealNote;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\User;

beforeEach(fn () => $this->seed(\Database\Seeders\DatabaseSeeder::class));

function dealStageToken(Tenant $tenant): string
{
    $plaintext = \Illuminate\Support\Str::random(40);
    ApiToken::create([
        'tenant_id' => $tenant->id,
        'name' => 'Test',
        'token' => hash('sha256', $plaintext),
    ]);

    return $plaintext;
}

it('rejects unauthenticated stage update', function () {
    $this->patchJson('/api/deals/1/stage', ['stage_slug' => 'contatado'])
        ->assertUnauthorized();
});

it('updates deal stage and returns 200', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $token = dealStageToken($tenant);

    $stage = PipelineStage::where('slug', 'contatado')->first();
    $deal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'pipeline_stage_id' => $stage->id,
    ]);

    $targetStage = PipelineStage::where('slug', 'proposta_enviada')->first();

    $this->withToken($token)
        ->patchJson("/api/deals/{$deal->id}/stage", ['stage_slug' => 'proposta_enviada'])
        ->assertOk()
        ->assertJsonStructure(['deal_id', 'stage', 'updated_at'])
        ->assertJson(['stage' => 'proposta_enviada']);

    expect($deal->fresh()->pipeline_stage_id)->toBe($targetStage->id);
});

it('creates a note when stage is updated', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $token = dealStageToken($tenant);

    $stage = PipelineStage::where('slug', 'contatado')->first();
    $deal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'pipeline_stage_id' => $stage->id,
    ]);

    $this->withToken($token)
        ->patchJson("/api/deals/{$deal->id}/stage", ['stage_slug' => 'visita_tecnica']);

    $note = DealNote::withoutGlobalScopes()->where('deal_id', $deal->id)->first();
    expect($note->body)->toContain('Visita Técnica');
});

it('validates stage_slug exists in pipeline_stages', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $token = dealStageToken($tenant);

    $stage = PipelineStage::where('slug', 'contatado')->first();
    $deal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'pipeline_stage_id' => $stage->id,
    ]);

    $this->withToken($token)
        ->patchJson("/api/deals/{$deal->id}/stage", ['stage_slug' => 'fase_inexistente'])
        ->assertUnprocessable();
});

it('returns 404 when deal belongs to a different tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $owner1 = User::factory()->businessOwner()->for($tenant1)->create();
    $owner2 = User::factory()->businessOwner()->for($tenant2)->create();
    $token1 = dealStageToken($tenant1);

    $stage = PipelineStage::where('slug', 'contatado')->first();
    $dealOfTenant2 = Deal::factory()->create([
        'tenant_id' => $tenant2->id,
        'user_id' => $owner2->id,
        'pipeline_stage_id' => $stage->id,
    ]);

    $this->withToken($token1)
        ->patchJson("/api/deals/{$dealOfTenant2->id}/stage", ['stage_slug' => 'proposta_enviada'])
        ->assertNotFound();
});
