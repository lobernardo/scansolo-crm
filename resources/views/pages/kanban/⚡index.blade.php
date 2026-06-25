<?php

use App\Enums\LeadSegment;
use App\Enums\LeadSource;
use App\Livewire\Forms\CreateLeadForm;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Services\DealService;
use App\Services\LeadService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Kanban')] class extends Component {
    #[On('dealUpdated')]
    public function refreshDeals(): void
    {
        unset($this->dealsByStage);
    }
    public CreateLeadForm $form;

    public bool $showCreateLeadModal = false;

    public ?Lead $existingLead = null;

    public bool $leadFound = false;

    public bool $leadSearched = false;

    public bool $showLossReasonModal = false;

    public ?int $pendingDealId = null;

    public ?int $pendingStageId = null;

    public ?int $pendingPosition = null;

    public string $lossReason = '';

    #[Computed]
    public function stages()
    {
        return PipelineStage::orderBy('sort_order')->get();
    }

    #[Computed]
    public function dealsByStage()
    {
        $query = Deal::with(['lead', 'owner', 'pipelineStage']);

        if (auth()->user()->isSalesperson()) {
            $query->where('user_id', auth()->id());
        }

        $deals = $query->orderBy('sort_order')->get();

        return $deals->groupBy('pipeline_stage_id');
    }

    public function searchLead(): void
    {
        $this->form->validateOnly('email');

        $this->existingLead = Lead::where('email', $this->form->email)->first();
        $this->leadFound = $this->existingLead !== null;
        $this->leadSearched = true;

        if ($this->leadFound) {
            $this->form->name = $this->existingLead->name;
            $this->form->company = $this->existingLead->company ?? '';
            $this->form->phone = $this->existingLead->phone ?? '';
            $this->form->city = $this->existingLead->city ?? '';
            $this->form->state = $this->existingLead->state ?? '';
            $this->form->segment = $this->existingLead->segment?->value ?? '';
            $this->form->source = $this->existingLead->source?->value ?? '';
        }
    }

    public function createLead(LeadService $leadService): void
    {
        if ($this->leadFound && $this->existingLead) {
            $this->form->validate([
                'email' => 'required|email',
                'deal_title' => 'required|min:2',
                'deal_value' => 'required|numeric|min:0.01',
            ]);

            $leadService->createDealForExistingLead(
                lead: $this->existingLead,
                owner: auth()->user(),
                dealTitle: $this->form->deal_title,
                dealValue: $this->form->deal_value,
            );
        } else {
            $this->form->validate();

            $leadService->createWithDeal(
                owner: auth()->user(),
                leadName: $this->form->name,
                leadEmail: $this->form->email,
                leadPhone: $this->form->phone ?: null,
                dealTitle: $this->form->deal_title,
                dealValue: $this->form->deal_value,
                company: $this->form->company ?: null,
                city: $this->form->city ?: null,
                state: $this->form->state ?: null,
                segment: $this->form->segment ? LeadSegment::tryFrom($this->form->segment) : null,
                source: $this->form->source ? LeadSource::tryFrom($this->form->source) : null,
            );
        }

        $this->resetCreateLeadModal();
        unset($this->dealsByStage);
        session()->flash('success', 'Negócio criado com sucesso!');
    }

    public function handleSort(int $stageId, int $dealId, int $position, DealService $dealService): void
    {
        $deal = Deal::findOrFail($dealId);

        $this->authorize('move', $deal);

        if ($dealService->requiresLossReason($stageId) && ! $deal->loss_reason) {
            $this->pendingDealId = $dealId;
            $this->pendingStageId = $stageId;
            $this->pendingPosition = $position;
            $this->showLossReasonModal = true;

            return;
        }

        $dealService->moveToStage($deal, $stageId, $position);
        unset($this->dealsByStage);
    }

    public function confirmLossReason(DealService $dealService): void
    {
        $this->validate([
            'lossReason' => 'required|min:2',
        ], [
            'lossReason.required' => 'O motivo da perda é obrigatório.',
            'lossReason.min' => 'O motivo da perda deve ter pelo menos 2 caracteres.',
        ]);

        $deal = Deal::findOrFail($this->pendingDealId);
        $this->authorize('move', $deal);

        $deal->update(['loss_reason' => $this->lossReason]);
        $dealService->moveToStage($deal, $this->pendingStageId, $this->pendingPosition);

        $this->resetLossReasonModal();
        unset($this->dealsByStage);
    }

    public function openCreateLeadModal(): void
    {
        $this->resetCreateLeadModal();
        $this->showCreateLeadModal = true;
    }

    private function resetCreateLeadModal(): void
    {
        $this->form->reset();
        $this->existingLead = null;
        $this->leadFound = false;
        $this->leadSearched = false;
        $this->showCreateLeadModal = false;
    }

    private function resetLossReasonModal(): void
    {
        $this->showLossReasonModal = false;
        $this->pendingDealId = null;
        $this->pendingStageId = null;
        $this->pendingPosition = null;
        $this->lossReason = '';
    }
};
?>

<div class="flex h-full flex-col">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-primary-dark">Kanban</h2>
        <x-button wire:click="openCreateLeadModal">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Novo Lead
        </x-button>
    </div>

    {{-- Kanban board — horizontal scroll contained here --}}
    <div class="flex-1 overflow-x-auto overflow-y-hidden">
        <div class="flex h-full gap-4 pb-4">
            @foreach($this->stages as $stage)
                <div class="flex w-72 flex-shrink-0 flex-col">
                    {{-- Column header --}}
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-primary-dark">{{ $stage->name }}</h3>
                        <span class="rounded-full bg-outline px-2 py-0.5 text-xs font-medium text-primary-grey">
                            {{ ($this->dealsByStage[$stage->id] ?? collect())->count() }}
                        </span>
                    </div>

                    {{-- Droppable column --}}
                    <div
                        x-data
                        x-on:dragover.prevent="$el.classList.add('ring-2', 'ring-primary/30')"
                        x-on:dragenter.prevent
                        x-on:dragleave.self="$el.classList.remove('ring-2', 'ring-primary/30')"
                        x-on:drop.prevent="
                            $el.classList.remove('ring-2', 'ring-primary/30');
                            const dealId = parseInt($event.dataTransfer.getData('deal-id'));
                            if (dealId) {
                                const cards = $el.querySelectorAll('[data-deal-id]');
                                let position = 0;
                                for (const card of cards) {
                                    const rect = card.getBoundingClientRect();
                                    if ($event.clientY > rect.top + rect.height / 2) position++;
                                }
                                $wire.handleSort({{ $stage->id }}, dealId, position);
                            }
                        "
                        class="flex-1 space-y-3 rounded-xl bg-bg-white p-3 shadow-sm transition-shadow"
                    >
                        @foreach(($this->dealsByStage[$stage->id] ?? collect()) as $deal)
                            <div
                                draggable="true"
                                data-deal-id="{{ $deal->id }}"
                                x-on:dragstart="
                                    $event.dataTransfer.setData('deal-id', '{{ $deal->id }}');
                                    $event.dataTransfer.effectAllowed = 'move';
                                    $el.classList.add('opacity-50');
                                "
                                x-on:dragend="$el.classList.remove('opacity-50')"
                                wire:key="deal-{{ $deal->id }}"
                                wire:click="$dispatch('openDealDetail', { dealId: {{ $deal->id }} })"
                                class="cursor-grab rounded-lg border border-outline bg-bg-white p-4 shadow-sm transition-shadow hover:shadow-md active:cursor-grabbing"
                            >
                                <h4 class="text-sm font-semibold text-primary-dark">{{ $deal->title }}</h4>
                                <p class="mt-1 text-xs text-primary-grey">{{ $deal->lead->name }}</p>
                                <div class="mt-3 flex items-center justify-between">
                                    <span class="text-sm font-medium text-primary">
                                        R$ {{ number_format((float) $deal->value, 2, ',', '.') }}
                                    </span>
                                    <span class="text-xs text-primary-grey">{{ $deal->owner->name }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Create Lead Modal --}}
    <x-modal name="showCreateLeadModal" maxWidth="lg">
        <x-slot:title>Novo Lead</x-slot:title>

        <form wire:submit="createLead" class="space-y-5">
            <p class="text-sm text-primary-grey">
                Pesquise por e-mail para encontrar um lead existente ou crie um novo.
            </p>

            {{-- Email search --}}
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <x-input
                        label="E-mail do lead"
                        type="email"
                        wire:model="form.email"
                        placeholder="email@exemplo.com"
                    />
                </div>
                <x-button type="button" variant="outline" wire:click="searchLead" class="mb-0.5">
                    <span wire:loading.remove wire:target="searchLead">Buscar</span>
                    <span wire:loading wire:target="searchLead">Buscando...</span>
                </x-button>
            </div>

            @if($leadSearched)
                @if($leadFound && $existingLead)
                    {{-- Existing lead found --}}
                    <div class="rounded-lg bg-secondary-green/10 px-4 py-3">
                        <p class="text-sm font-medium text-secondary-green">Lead encontrado!</p>
                        <p class="mt-1 text-sm text-primary-dark">{{ $existingLead->name }} — {{ $existingLead->email }}</p>
                        @if($existingLead->phone)
                            <p class="text-xs text-primary-grey">{{ $existingLead->phone }}</p>
                        @endif
                    </div>
                @else
                    {{-- New lead fields --}}
                    <div class="rounded-lg bg-secondary-yellow/10 px-4 py-3">
                        <p class="text-sm font-medium text-secondary-yellow">Nenhum lead encontrado. Preencha os dados para criar um novo.</p>
                    </div>

                    <x-input
                        label="Nome do lead"
                        wire:model="form.name"
                        placeholder="Nome completo"
                    />

                    <x-input
                        label="Empresa"
                        wire:model="form.company"
                        placeholder="Razão social ou nome da empresa"
                    />

                    <x-input
                        label="Telefone"
                        wire:model="form.phone"
                        placeholder="(00) 00000-0000"
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <x-input
                            label="Cidade"
                            wire:model="form.city"
                            placeholder="São Paulo"
                        />
                        <x-select
                            label="Estado"
                            wire:model="form.state"
                            placeholder="UF"
                        >
                            @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                                <option value="{{ $uf }}" @selected(old('state') === $uf)>{{ $uf }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-select
                            label="Segmento"
                            wire:model="form.segment"
                            placeholder="Selecione"
                        >
                            @foreach(\App\Enums\LeadSegment::cases() as $case)
                                <option value="{{ $case->value }}">{{ $case->label() }}</option>
                            @endforeach
                        </x-select>

                        <x-select
                            label="Origem"
                            wire:model="form.source"
                            placeholder="Selecione"
                        >
                            @foreach(\App\Enums\LeadSource::cases() as $case)
                                <option value="{{ $case->value }}">{{ $case->label() }}</option>
                            @endforeach
                        </x-select>
                    </div>
                @endif

                {{-- Deal fields (always shown after search) --}}
                <div class="border-t border-outline pt-4">
                    <p class="mb-3 text-sm font-medium text-primary-dark">Dados do negócio</p>

                    <div class="space-y-4">
                        <x-input
                            label="Título do negócio"
                            wire:model="form.deal_title"
                            placeholder="Ex: Proposta comercial"
                        />

                        <x-input
                            label="Valor (R$)"
                            type="number"
                            wire:model="form.deal_value"
                            placeholder="0,00"
                            step="0.01"
                            min="0.01"
                        />
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-end gap-3">
                <x-button variant="outline" type="button" x-on:click="open = false">
                    Cancelar
                </x-button>
                @if($leadSearched)
                    <x-button type="submit">
                        <span wire:loading.remove wire:target="createLead">Criar negócio</span>
                        <span wire:loading wire:target="createLead">Criando...</span>
                    </x-button>
                @endif
            </div>
        </form>
    </x-modal>

    {{-- Deal Detail Slide-over --}}
    <livewire:deal-detail />

    {{-- Loss Reason Modal --}}
    <x-modal name="showLossReasonModal" maxWidth="md">
        <x-slot:title>Motivo da perda</x-slot:title>

        <form wire:submit="confirmLossReason" class="space-y-5">
            <p class="text-sm text-primary-grey">
                Para mover este negócio para "Lost", informe o motivo da perda.
            </p>

            <x-input
                label="Motivo da perda"
                wire:model="lossReason"
                name="lossReason"
                placeholder="Ex: Cliente optou pela concorrência"
            />

            <div class="flex items-center justify-end gap-3">
                <x-button variant="outline" type="button" x-on:click="open = false">
                    Cancelar
                </x-button>
                <x-button type="submit" variant="danger">
                    <span wire:loading.remove wire:target="confirmLossReason">Confirmar perda</span>
                    <span wire:loading wire:target="confirmLossReason">Confirmando...</span>
                </x-button>
            </div>
        </form>
    </x-modal>
</div>
