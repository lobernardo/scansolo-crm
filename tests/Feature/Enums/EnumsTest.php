<?php

use App\Enums\AccountStatus;
use App\Enums\ConnectionStatus;
use App\Enums\InvitationState;
use App\Enums\UserRole;
use App\Models\InvitationStatus;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionStatus;

beforeEach(fn () => $this->seed(\Database\Seeders\DatabaseSeeder::class));

// --- UserRole ---

it('UserRole enum values match seeded roles', function () {
    expect(Role::where('name', UserRole::BusinessOwner->value)->exists())->toBeTrue();
    expect(Role::where('name', UserRole::Salesperson->value)->exists())->toBeTrue();
});

it('User isBusinessOwner uses UserRole enum', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->businessOwner()->for($tenant)->create();
    $sp = User::factory()->salesperson()->for($tenant)->create();

    expect($owner->isBusinessOwner())->toBeTrue()
        ->and($owner->isSalesperson())->toBeFalse()
        ->and($sp->isBusinessOwner())->toBeFalse()
        ->and($sp->isSalesperson())->toBeTrue();
});

// --- AccountStatus ---

it('AccountStatus enum values match seeded statuses', function () {
    expect(UserStatus::where('name', AccountStatus::Active->value)->exists())->toBeTrue();
    expect(UserStatus::where('name', AccountStatus::Inactive->value)->exists())->toBeTrue();
});

it('User isActive uses AccountStatus enum', function () {
    $tenant = Tenant::factory()->create();
    $active = User::factory()->active()->for($tenant)->create();
    $inactive = User::factory()->inactive()->for($tenant)->create();

    expect($active->isActive())->toBeTrue()
        ->and($inactive->isActive())->toBeFalse();
});

// --- ConnectionStatus ---

it('ConnectionStatus enum values match seeded statuses', function () {
    expect(WhatsappConnectionStatus::where('name', ConnectionStatus::Connected->value)->exists())->toBeTrue();
    expect(WhatsappConnectionStatus::where('name', ConnectionStatus::Disconnected->value)->exists())->toBeTrue();
});

it('WhatsappConnection factory states use ConnectionStatus enum', function () {
    $tenant = Tenant::factory()->create();

    $disconnected = WhatsappConnection::factory()->create(['tenant_id' => $tenant->id]);
    expect($disconnected->whatsappConnectionStatus->name)->toBe(ConnectionStatus::Disconnected->value);
});

// --- InvitationState ---

it('InvitationState enum values match seeded statuses', function () {
    expect(InvitationStatus::where('name', InvitationState::Pending->value)->exists())->toBeTrue();
    expect(InvitationStatus::where('name', InvitationState::Accepted->value)->exists())->toBeTrue();
    expect(InvitationStatus::where('name', InvitationState::Revoked->value)->exists())->toBeTrue();
});

// --- Enum integrity (values cannot silently change) ---

it('enum values are stable and match expected strings', function () {
    expect(UserRole::BusinessOwner->value)->toBe('Business Owner')
        ->and(UserRole::Salesperson->value)->toBe('Salesperson')
        ->and(AccountStatus::Active->value)->toBe('Active')
        ->and(AccountStatus::Inactive->value)->toBe('Inactive')
        ->and(ConnectionStatus::Connected->value)->toBe('Connected')
        ->and(ConnectionStatus::Disconnected->value)->toBe('Disconnected')
        ->and(InvitationState::Pending->value)->toBe('Pending')
        ->and(InvitationState::Accepted->value)->toBe('Accepted')
        ->and(InvitationState::Revoked->value)->toBe('Revoked');
});
