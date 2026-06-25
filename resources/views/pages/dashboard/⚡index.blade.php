<?php

use App\Enums\DealServiceType;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Dashboard')] class extends Component {
    public function mount(): void
    {
        if (auth()->user()->isSalesperson()) {
            $this->redirect(route('kanban.index'));
        }
    }

    #[Computed]
    public function totalLeads(): int
    {
        return Lead::count();
    }

    #[Computed]
    public function activeDeals(): int
    {
        return Deal::whereHas('pipelineStage', fn ($q) => $q->where('is_terminal', false))->count();
    }

    #[Computed]
    public function wonDealsValue(): float
    {
        return (float) Deal::whereHas('pipelineStage', fn ($q) => $q->where('is_won', true))->sum('value');
    }

    #[Computed]
    public function wonDealsCount(): int
    {
        return Deal::whereHas('pipelineStage', fn ($q) => $q->where('is_won', true))->count();
    }

    #[Computed]
    public function lostDealsCount(): int
    {
        return Deal::whereHas('pipelineStage', fn ($q) => $q->where('is_terminal', true)->where('is_won', false))->count();
    }

    #[Computed]
    public function totalAreaMapped(): float
    {
        return (float) Deal::whereHas('pipelineStage', fn ($q) => $q->where('is_won', true))
            ->whereNotNull('area_m2')
            ->sum('area_m2');
    }

    #[Computed]
    public function scheduledServicesCount(): int
    {
        return Deal::whereHas('pipelineStage', fn ($q) => $q->where('is_terminal', false))
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '>=', Carbon::today())
            ->count();
    }

    #[Computed]
    public function conversionRate(): float
    {
        $total = $this->wonDealsCount + $this->lostDealsCount;
        if ($total === 0) {
            return 0.0;
        }

        return round($this->wonDealsCount / $total * 100, 1);
    }

    #[Computed]
    public function dealsByStage(): array
    {
        $stages = PipelineStage::orderBy('sort_order')->get();
        $counts = Deal::selectRaw('pipeline_stage_id, count(*) as total')
            ->groupBy('pipeline_stage_id')
            ->pluck('total', 'pipeline_stage_id');

        return $stages->map(fn ($stage) => [
            'name' => $stage->name,
            'count' => (int) ($counts[$stage->id] ?? 0),
            'is_won' => $stage->is_won,
            'is_terminal' => $stage->is_terminal,
        ])->toArray();
    }

    #[Computed]
    public function topServiceType(): ?string
    {
        $top = Deal::whereHas('pipelineStage', fn ($q) => $q->where('is_terminal', false))
            ->whereNotNull('service_type')
            ->selectRaw('service_type, count(*) as total')
            ->groupBy('service_type')
            ->orderByDesc('total')
            ->first();

        if (! $top) {
            return null;
        }

        return $top->service_type?->label();
    }

    #[Computed]
    public function recentActivity(): \Illuminate\Support\Collection
    {
        return Deal::with(['lead', 'pipelineStage', 'owner'])
            ->latest('updated_at')
            ->limit(8)
            ->get();
    }
};
?>

<div class="space-y-6">

    {{-- KPI row 1: core metrics --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl bg-bg-white p-5 shadow-sm border border-outline">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-primary-grey uppercase tracking-wide">Total de Leads</p>
                <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10">
                    <svg class="size-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
            <p class="mt-3 text-3xl font-bold text-primary-dark">{{ $this->totalLeads }}</p>
        </div>

        <div class="rounded-xl bg-bg-white p-5 shadow-sm border border-outline">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-primary-grey uppercase tracking-wide">Negócios Ativos</p>
                <div class="flex size-9 items-center justify-center rounded-lg bg-secondary-yellow/15">
                    <svg class="size-4 text-secondary-yellow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
            </div>
            <p class="mt-3 text-3xl font-bold text-primary-dark">{{ $this->activeDeals }}</p>
        </div>

        <div class="rounded-xl bg-bg-white p-5 shadow-sm border border-outline">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-primary-grey uppercase tracking-wide">Receita Ganha</p>
                <div class="flex size-9 items-center justify-center rounded-lg bg-secondary-green/15">
                    <svg class="size-4 text-secondary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="mt-3 text-2xl font-bold text-secondary-green">R$ {{ number_format($this->wonDealsValue, 0, ',', '.') }}</p>
        </div>

        <div class="rounded-xl bg-bg-white p-5 shadow-sm border border-outline">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-primary-grey uppercase tracking-wide">Taxa de Conversão</p>
                <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10">
                    <svg class="size-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <p class="mt-3 text-3xl font-bold text-primary-dark">{{ $this->conversionRate }}%</p>
        </div>
    </div>

    {{-- KPI row 2: GPR-specific --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl bg-bg-white p-5 shadow-sm border border-outline">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-primary-grey uppercase tracking-wide">Área Total Mapeada</p>
                <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10">
                    <svg class="size-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                </div>
            </div>
            <p class="mt-3 text-3xl font-bold text-primary-dark">{{ number_format($this->totalAreaMapped, 0, ',', '.') }} <span class="text-base font-normal text-primary-grey">m²</span></p>
            <p class="text-xs text-primary-grey mt-1">em negócios ganhos</p>
        </div>

        <div class="rounded-xl bg-bg-white p-5 shadow-sm border border-outline">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-primary-grey uppercase tracking-wide">Serviços Agendados</p>
                <div class="flex size-9 items-center justify-center rounded-lg bg-secondary-yellow/15">
                    <svg class="size-4 text-secondary-yellow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <p class="mt-3 text-3xl font-bold text-primary-dark">{{ $this->scheduledServicesCount }}</p>
            <p class="text-xs text-primary-grey mt-1">a partir de hoje</p>
        </div>

        <div class="rounded-xl bg-bg-white p-5 shadow-sm border border-outline">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-primary-grey uppercase tracking-wide">Serviço mais Demandado</p>
                <div class="flex size-9 items-center justify-center rounded-lg bg-secondary-purple/15">
                    <svg class="size-4 text-secondary-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                </div>
            </div>
            <p class="mt-3 text-xl font-bold text-primary-dark leading-tight">{{ $this->topServiceType ?? '—' }}</p>
            <p class="text-xs text-primary-grey mt-1">negócios ativos</p>
        </div>
    </div>

    {{-- Bottom row: pipeline chart + activity feed --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        @php $maxCount = max(array_column($this->dealsByStage, 'count') ?: [1]); @endphp
        <div class="rounded-xl bg-bg-white p-6 shadow-sm border border-outline">
            <h3 class="mb-4 text-sm font-semibold text-primary-dark">Negócios por Etapa</h3>
            <div class="space-y-3">
                @foreach($this->dealsByStage as $stage)
                    @php
                        $pct = $maxCount > 0 ? ($stage['count'] / $maxCount) * 100 : 0;
                        $barColor = match(true) {
                            $stage['is_won'] => 'bg-secondary-green',
                            $stage['is_terminal'] => 'bg-secondary-red',
                            default => 'bg-primary',
                        };
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-primary-grey truncate max-w-xs">{{ $stage['name'] }}</span>
                            <span class="font-semibold text-primary-dark ml-2">{{ $stage['count'] }}</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-bg-light overflow-hidden">
                            <div class="{{ $barColor }} h-full rounded-full" style="width: {{ number_format($pct, 2, '.', '') }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl bg-bg-white p-6 shadow-sm border border-outline">
            <h3 class="mb-4 text-sm font-semibold text-primary-dark">Atividade Recente</h3>
            @if($this->recentActivity->isEmpty())
                <p class="text-sm text-primary-grey">Nenhum negócio registrado ainda.</p>
            @else
                <div class="divide-y divide-outline">
                    @foreach($this->recentActivity as $deal)
                        @php
                            $tagColor = match(true) {
                                $deal->pipelineStage->is_won => 'text-secondary-green bg-secondary-green/10',
                                $deal->pipelineStage->is_terminal => 'text-secondary-red bg-secondary-red/10',
                                default => 'text-primary bg-primary/10',
                            };
                        @endphp
                        <div class="flex items-center justify-between py-2.5">
                            <div class="min-w-0 flex-1 pr-3">
                                <p class="text-sm font-medium text-primary-dark truncate">{{ $deal->title }}</p>
                                <p class="text-xs text-primary-grey truncate">{{ $deal->lead->name }}@if($deal->lead->company) · {{ $deal->lead->company }}@endif</p>
                            </div>
                            <div class="flex-shrink-0 flex flex-col items-end gap-1">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $tagColor }}">{{ $deal->pipelineStage->name }}</span>
                                <span class="text-xs text-primary-grey">{{ $deal->updated_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>
