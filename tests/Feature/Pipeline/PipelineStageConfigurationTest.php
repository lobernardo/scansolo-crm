<?php

use App\Models\PipelineStage;
use Illuminate\Support\Facades\DB;

beforeEach(fn () => $this->seed(\Database\Seeders\DatabaseSeeder::class));

it('pipeline has exactly 7 stages', function () {
    expect(DB::table('pipeline_stages')->count())->toBe(7);
});

it('all stage names are in Brazilian Portuguese', function () {
    $names = PipelineStage::orderBy('sort_order')->pluck('name')->toArray();

    expect($names)->toBe([
        'Novo Lead',
        'Contatado',
        'Visita Técnica',
        'Proposta Enviada',
        'Negociação',
        'Ganho',
        'Perdido',
    ]);
});

it('sort order is sequential from 1 to 7', function () {
    $sortOrders = PipelineStage::orderBy('sort_order')->pluck('sort_order')->toArray();

    expect($sortOrders)->toBe([1, 2, 3, 4, 5, 6, 7]);
});

it('only Ganho stage has is_won flag', function () {
    $wonStages = PipelineStage::where('is_won', true)->get();

    expect($wonStages)->toHaveCount(1)
        ->and($wonStages->first()->name)->toBe('Ganho')
        ->and($wonStages->first()->sort_order)->toBe(6);
});

it('only terminal stages are Ganho and Perdido', function () {
    $terminalStages = PipelineStage::where('is_terminal', true)->orderBy('sort_order')->get();

    expect($terminalStages)->toHaveCount(2)
        ->and($terminalStages[0]->name)->toBe('Ganho')
        ->and($terminalStages[1]->name)->toBe('Perdido');
});

it('Perdido stage is terminal but not won', function () {
    $perdido = PipelineStage::where('sort_order', 7)->first();

    expect($perdido->name)->toBe('Perdido')
        ->and($perdido->is_terminal)->toBeTrue()
        ->and($perdido->is_won)->toBeFalse();
});

it('Visita Tecnica is between Contatado and Proposta Enviada', function () {
    $contatado = PipelineStage::where('sort_order', 2)->first();
    $visitaTecnica = PipelineStage::where('sort_order', 3)->first();
    $propostaEnviada = PipelineStage::where('sort_order', 4)->first();

    expect($contatado->name)->toBe('Contatado')
        ->and($visitaTecnica->name)->toBe('Visita Técnica')
        ->and($propostaEnviada->name)->toBe('Proposta Enviada');
});

it('active (non-terminal) stages are the first 5 in order', function () {
    $activeStages = PipelineStage::where('is_terminal', false)->orderBy('sort_order')->pluck('name')->toArray();

    expect($activeStages)->toBe([
        'Novo Lead',
        'Contatado',
        'Visita Técnica',
        'Proposta Enviada',
        'Negociação',
    ]);
});

it('seeder is idempotent - running twice preserves exactly 7 stages', function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    expect(DB::table('pipeline_stages')->count())->toBe(7);

    $names = PipelineStage::orderBy('sort_order')->pluck('name')->toArray();
    expect($names)->toBe([
        'Novo Lead',
        'Contatado',
        'Visita Técnica',
        'Proposta Enviada',
        'Negociação',
        'Ganho',
        'Perdido',
    ]);
});
