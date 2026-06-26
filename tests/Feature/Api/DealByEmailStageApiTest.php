<?php

use App\Models\ApiToken;
use App\Models\Deal;
use App\Models\DealNote;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\User;

beforeEach(fn () => $this->seed(\Database\Seeders\DatabaseSeeder::class));

function byEmailToken(Tenant $tenant): string
{
    $plaintext = \Illuminate\Support\Str::random(40);
    ApiToken::create([
        'tenant_id' => $tenant->id,
        'name' => 'Test',
        'token' => hash('sha256', $plaintext),
    ]);

    return $plaintext;
}

it('rejects unauthenticated requests', function () {
    $this->patchJson('/api/deals/by-email/stage', ['email' => 'a@b.com', 'stage_slug' => 'contatado'])
        ->assertUnauthorized();
});

it('returns 404 when lead email is not found in tenant', function () {
    $tenant = Tenant::factory()->create();
    User::factory()->businessOwner()->for($tenant)->create();
    $token = byEmailToken($tenant);

    $this->withToken($token)
        ->patchJson('/api/deals/by-email/stage', [
            'email' => 'naoexiste@exemplo.com',
            'stage_slug' => 'contatado',
        ])
        ->assertNotFound()
        ->assertJson(['ok' => false, 'error' => 'Deal not found']);
});

it('returns 404 when lead exists but has no deals', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $token = byEmailToken($tenant);

    Lead::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'email' => 'semdeal@exemplo.com',
    ]);

    $this->withToken($token)
        ->patchJson('/api/deals/by-email/stage', [
            'email' => 'semdeal@exemplo.com',
            'stage_slug' => 'contatado',
        ])
        ->assertNotFound()
        ->assertJson(['ok' => false, 'error' => 'Deal not found']);
});

it('updates the most recent deal stage and returns ok', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $token = byEmailToken($tenant);

    $initialStage = PipelineStage::where('slug', 'contatado')->first();
    $targetStage = PipelineStage::where('slug', 'proposta_enviada')->first();

    $lead = Lead::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'email' => 'cliente@empresa.com',
    ]);

    $deal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'lead_id' => $lead->id,
        'pipeline_stage_id' => $initialStage->id,
    ]);

    $this->withToken($token)
        ->patchJson('/api/deals/by-email/stage', [
            'email' => 'cliente@empresa.com',
            'stage_slug' => 'proposta_enviada',
        ])
        ->assertOk()
        ->assertJson(['ok' => true, 'deal_id' => $deal->id, 'stage' => 'proposta_enviada']);

    expect($deal->fresh()->pipeline_stage_id)->toBe($targetStage->id);
});

it('targets the latest deal when lead has multiple deals', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $token = byEmailToken($tenant);

    $stage = PipelineStage::where('slug', 'contatado')->first();

    $lead = Lead::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'email' => 'multi@empresa.com',
    ]);

    $olderDeal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'lead_id' => $lead->id,
        'pipeline_stage_id' => $stage->id,
        'created_at' => now()->subDays(5),
    ]);

    $latestDeal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'lead_id' => $lead->id,
        'pipeline_stage_id' => $stage->id,
        'created_at' => now(),
    ]);

    $response = $this->withToken($token)
        ->patchJson('/api/deals/by-email/stage', [
            'email' => 'multi@empresa.com',
            'stage_slug' => 'proposta_enviada',
        ])
        ->assertOk();

    expect($response->json('deal_id'))->toBe($latestDeal->id);
});

it('creates a deal note when stage is updated', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $token = byEmailToken($tenant);

    $stage = PipelineStage::where('slug', 'contatado')->first();

    $lead = Lead::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'email' => 'nota@empresa.com',
    ]);

    $deal = Deal::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'lead_id' => $lead->id,
        'pipeline_stage_id' => $stage->id,
    ]);

    $this->withToken($token)
        ->patchJson('/api/deals/by-email/stage', [
            'email' => 'nota@empresa.com',
            'stage_slug' => 'proposta_enviada',
        ])
        ->assertOk();

    $note = DealNote::withoutGlobalScopes()->where('deal_id', $deal->id)->first();
    expect($note)->not->toBeNull()
        ->and($note->body)->toContain('Proposta Enviada');
});

it('validates that stage_slug must exist in pipeline_stages', function () {
    $tenant = Tenant::factory()->create();
    User::factory()->businessOwner()->for($tenant)->create();
    $token = byEmailToken($tenant);

    $this->withToken($token)
        ->patchJson('/api/deals/by-email/stage', [
            'email' => 'a@b.com',
            'stage_slug' => 'slug_inexistente',
        ])
        ->assertUnprocessable();
});

it('does not update a deal from another tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $owner1 = User::factory()->businessOwner()->for($tenant1)->create();
    $owner2 = User::factory()->businessOwner()->for($tenant2)->create();
    $token1 = byEmailToken($tenant1);

    $stage = PipelineStage::where('slug', 'contatado')->first();

    $lead2 = Lead::factory()->create([
        'tenant_id' => $tenant2->id,
        'user_id' => $owner2->id,
        'email' => 'outro@tenant.com',
    ]);

    Deal::factory()->create([
        'tenant_id' => $tenant2->id,
        'user_id' => $owner2->id,
        'lead_id' => $lead2->id,
        'pipeline_stage_id' => $stage->id,
    ]);

    $this->withToken($token1)
        ->patchJson('/api/deals/by-email/stage', [
            'email' => 'outro@tenant.com',
            'stage_slug' => 'proposta_enviada',
        ])
        ->assertNotFound();
});
