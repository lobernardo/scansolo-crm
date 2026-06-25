<?php

use App\Models\ApiToken;
use App\Models\Tenant;
use App\Models\User;

beforeEach(fn () => $this->seed(\Database\Seeders\DatabaseSeeder::class));

it('guest cannot generate api token', function () {
    $this->postJson('/api/tenant/token')->assertUnauthorized();
});

it('authenticated user can generate api token', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    $response = $this->actingAs($owner)
        ->postJson('/api/tenant/token');

    $response->assertOk()
        ->assertJsonStructure(['token', 'message']);

    expect(ApiToken::where('tenant_id', $tenant->id)->exists())->toBeTrue();
});

it('returns plaintext token in response', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    $response = $this->actingAs($owner)
        ->postJson('/api/tenant/token');

    $plaintext = $response->json('token');
    expect(strlen($plaintext))->toBe(40);

    $dbToken = ApiToken::where('tenant_id', $tenant->id)->first();
    expect($dbToken->token)->toBe(hash('sha256', $plaintext));
});

it('regenerating token invalidates previous token', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    $response1 = $this->actingAs($owner)->postJson('/api/tenant/token');
    $token1 = $response1->json('token');

    $response2 = $this->actingAs($owner)->postJson('/api/tenant/token');
    $token2 = $response2->json('token');

    expect($token1)->not->toBe($token2);
    expect(ApiToken::where('tenant_id', $tenant->id)->count())->toBe(1);

    $this->withToken($token1)
        ->postJson('/api/leads/inbound', ['name' => 'Test', 'company' => 'Test'])
        ->assertUnauthorized();
});

it('tokens are isolated per tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $owner1 = User::factory()->businessOwner()->for($tenant1)->create();
    $owner2 = User::factory()->businessOwner()->for($tenant2)->create();

    $this->actingAs($owner1)->postJson('/api/tenant/token');
    $this->actingAs($owner2)->postJson('/api/tenant/token');

    expect(ApiToken::where('tenant_id', $tenant1->id)->count())->toBe(1);
    expect(ApiToken::where('tenant_id', $tenant2->id)->count())->toBe(1);
});

it('last_used_at is updated on api call', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    $response = $this->actingAs($owner)->postJson('/api/tenant/token');
    $plaintext = $response->json('token');

    expect(ApiToken::where('tenant_id', $tenant->id)->first()->last_used_at)->toBeNull();

    $this->seed(\Database\Seeders\DatabaseSeeder::class);
    $this->withToken($plaintext)
        ->postJson('/api/leads/inbound', ['name' => 'Test', 'company' => 'Test Co']);

    expect(ApiToken::where('tenant_id', $tenant->id)->first()->last_used_at)->not->toBeNull();
});
