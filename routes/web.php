<?php

use App\Http\Controllers\Api\TenantTokenController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response('OK', 200));

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('kanban.index')
        : redirect()->route('login');
});

// Placeholder routes for navigation - will be replaced by Livewire page components in later phases
Route::middleware(['auth', 'active'])->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard.index')->name('dashboard.index');
    Route::livewire('/leads', 'pages::leads.index')->name('leads.index');
    Route::livewire('/kanban', 'pages::kanban.index')->name('kanban.index');
    Route::livewire('/agenda', 'pages::agenda.index')->name('agenda.index');
    Route::livewire('/relatorios', 'pages::relatorios.index')->name('relatorios.index');
    Route::livewire('/projetos', 'pages::projetos.index')->name('projetos.index');
    Route::livewire('/team', 'pages::team.index')->name('team.index');
    Route::livewire('/settings/whatsapp', 'pages::settings.whatsapp')->name('settings.whatsapp');
    Route::livewire('/settings/api-integration', 'pages::settings.api-integration')->name('settings.api-integration');
    Route::redirect('/settings', '/settings/api-integration')->name('settings.index');

    Route::post('/api/tenant/token', TenantTokenController::class)->name('api.tenant.token');

    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});

Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::auth.login')->name('login');
    Route::livewire('/register', 'pages::auth.register')->name('register');
    Route::livewire('/register/invite/{token}', 'pages::auth.register-invited')->name('register.invited');
    Route::livewire('/forgot-password', 'pages::auth.forgot-password')->name('password.request');
    Route::livewire('/reset-password/{token}', 'pages::auth.reset-password')->name('password.reset');
});
