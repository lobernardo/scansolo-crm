<?php

use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(fn () => $this->seed(\Database\Seeders\DatabaseSeeder::class));

function propostaStage(): PipelineStage
{
    return PipelineStage::where('slug', 'proposta_enviada')->firstOrFail();
}

function dealInProposta(Tenant $tenant, User $owner, array $overrides = []): Deal
{
    $lead = Lead::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'phone' => '11999999999',
    ]);

    return Deal::factory()->create(array_merge([
        'tenant_id' => $tenant->id,
        'user_id' => $owner->id,
        'lead_id' => $lead->id,
        'pipeline_stage_id' => propostaStage()->id,
    ], $overrides));
}

it('exits successfully with no deals in proposta_enviada', function () {
    $this->artisan('followup:send')->assertSuccessful();
});

it('does not send follow-up when deal is less than 2 days old', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    dealInProposta($tenant, $owner, ['updated_at' => now()->subDay()]);

    config(['services.evolution_api.base_url' => null]);
    Log::spy();

    $this->artisan('followup:send')->assertSuccessful();

    Log::shouldNotHaveReceived('info');
});

it('sends follow-up 1 at D+2 and marks followup_1_sent_at', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    $deal = dealInProposta($tenant, $owner);
    \Illuminate\Support\Facades\DB::table('deals')
        ->where('id', $deal->id)
        ->update(['updated_at' => now()->subDays(2)]);

    config(['services.evolution_api.base_url' => null]);
    Log::spy();

    $this->artisan('followup:send')->assertSuccessful();

    Log::shouldHaveReceived('info')->once()->withArgs(fn ($msg) => str_contains($msg, 'followup_1_sent_at'));

    expect($deal->fresh()->followup_1_sent_at)->not->toBeNull();
    expect($deal->fresh()->followup_2_sent_at)->toBeNull();
});

it('sends follow-up 2 at D+5 when follow-up 1 is already sent', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    $deal = dealInProposta($tenant, $owner);
    \Illuminate\Support\Facades\DB::table('deals')
        ->where('id', $deal->id)
        ->update([
            'updated_at' => now()->subDays(5),
            'followup_1_sent_at' => now()->subDays(3),
        ]);

    config(['services.evolution_api.base_url' => null]);
    Log::spy();

    $this->artisan('followup:send')->assertSuccessful();

    Log::shouldHaveReceived('info')->once()->withArgs(fn ($msg) => str_contains($msg, 'followup_2_sent_at'));

    expect($deal->fresh()->followup_2_sent_at)->not->toBeNull();
    expect($deal->fresh()->followup_3_sent_at)->toBeNull();
});

it('sends follow-up 3 at D+10 when follow-ups 1 and 2 are sent', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    $deal = dealInProposta($tenant, $owner);
    \Illuminate\Support\Facades\DB::table('deals')
        ->where('id', $deal->id)
        ->update([
            'updated_at' => now()->subDays(10),
            'followup_1_sent_at' => now()->subDays(8),
            'followup_2_sent_at' => now()->subDays(5),
        ]);

    config(['services.evolution_api.base_url' => null]);
    Log::spy();

    $this->artisan('followup:send')->assertSuccessful();

    Log::shouldHaveReceived('info')->once()->withArgs(fn ($msg) => str_contains($msg, 'followup_3_sent_at'));

    expect($deal->fresh()->followup_3_sent_at)->not->toBeNull();
});

it('does not resend a follow-up that was already sent', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    $deal = dealInProposta($tenant, $owner);
    \Illuminate\Support\Facades\DB::table('deals')
        ->where('id', $deal->id)
        ->update([
            'updated_at' => now()->subDays(3),
            'followup_1_sent_at' => now()->subDay(),
        ]);

    config(['services.evolution_api.base_url' => null]);
    Log::spy();

    $this->artisan('followup:send')->assertSuccessful();

    Log::shouldNotHaveReceived('info');
});

it('calls Evolution API when EVOLUTION_API_URL is configured', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();

    $deal = dealInProposta($tenant, $owner);
    \Illuminate\Support\Facades\DB::table('deals')
        ->where('id', $deal->id)
        ->update(['updated_at' => now()->subDays(2)]);

    config([
        'services.evolution_api.base_url' => 'http://fake-evolution.test',
        'services.evolution_api.api_key' => 'test-key',
        'services.evolution_api.instance' => 'test-instance',
    ]);

    Http::fake([
        'fake-evolution.test/*' => Http::response(['key' => ['id' => 'msg-id']], 200),
    ]);

    $this->artisan('followup:send')->assertSuccessful();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/message/sendText/test-instance'));

    expect($deal->fresh()->followup_1_sent_at)->not->toBeNull();
});
