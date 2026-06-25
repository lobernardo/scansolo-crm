<?php

use App\Enums\LeadSegment;
use App\Enums\LeadSource;
use App\Livewire\Forms\CreateLeadForm;
use App\Models\Deal;
use App\Models\Lead;
use App\Services\LeadService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Leads & Contatos')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $filterSegment = '';

    public string $filterSource = '';

    public CreateLeadForm $form;

    public bool $showCreateLeadModal = false;

    public ?Lead $existingLead = null;

    public bool $leadFound = false;

    public bool $leadSearched = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSegment(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSource(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function leads()
    {
        return Lead::with(['owner', 'deals' => fn ($q) => $q->whereHas('pipelineStage', fn ($q) => $q->where('is_terminal', false))->latest()])
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('company', 'like', "%{$this->search}%")))
            ->when($this->filterSegment, fn ($q) => $q->where('segment', $this->filterSegment))
            ->when($this->filterSource, fn ($q) => $q->where('source', $this->filterSource))
            ->latest()
            ->paginate(20);
    }

    public function openCreateLeadModal(): void
    {
        $this->form->reset();
        $this->existingLead = null;
        $this->leadFound = false;
        $this->leadSearched = false;
        $this->showCreateLeadModal = true;
    }

    public function closeCreateLeadModal(): void
    {
        $this->showCreateLeadModal = false;
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

            $deal = $leadService->createDealForExistingLead(
                lead: $this->existingLead,
                owner: auth()->user(),
                dealTitle: $this->form->deal_title,
                dealValue: $this->form->deal_value,
            );
        } else {
            $this->form->validate();

            $deal = $leadService->createWithDeal(
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

        $this->showCreateLeadModal = false;
        unset($this->leads);
        $this->dispatch('openDealDetail', dealId: $deal->id);
        session()->flash('success', 'Lead criado com sucesso!');
    }

    public function openLeadDeal(int $leadId): void
    {
        $lead = Lead::find($leadId);
        if (! $lead) {
            return;
        }

        $activeDeal = Deal::where('lead_id', $leadId)
            ->whereHas('pipelineStage', fn ($q) => $q->where('is_terminal', false))
            ->latest()
            ->first();

        if ($activeDeal) {
            $this->dispatch('openDealDetail', dealId: $activeDeal->id);
        } else {
            $this->form->reset();
            $this->form->email = $lead->email;
            $this->form->name = $lead->name;
            $this->form->company = $lead->company ?? '';
            $this->existingLead = $lead;
            $this->leadFound = true;
            $this->leadSearched = true;
            $this->showCreateLeadModal = true;
        }
    }
};
?>

<div>
    <livewire:deal-detail />

    {{-- Header bar --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
            {{-- Search --}}
            <div class="relative max-w-sm flex-1">
                <svg class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-primary-grey" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nome ou empresa…"
                    class="w-full rounded-lg border border-outline bg-bg-white py-2 pl-9 pr-4 text-sm text-primary-dark placeholder-primary-grey focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"/>
            </div>

            {{-- Segment filter --}}
            <select wire:model.live="filterSegment"
                class="rounded-lg border border-outline bg-bg-white px-3 py-2 text-sm text-primary-dark focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                <option value="">Todos os segmentos</option>
                @foreach(\App\Enums\LeadSegment::cases() as $case)
                    <option value="{{ $case->value }}" @selected($filterSegment === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>

            {{-- Source filter --}}
            <select wire:model.live="filterSource"
                class="rounded-lg border border-outline bg-bg-white px-3 py-2 text-sm text-primary-dark focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                <option value="">Todas as origens</option>
                @foreach(\App\Enums\LeadSource::cases() as $case)
                    <option value="{{ $case->value }}" @selected($filterSource === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>

        <x-button wire:click="openCreateLeadModal">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Lead
        </x-button>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-outline bg-bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-outline bg-bg-light text-left">
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-primary-grey">Nome</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-primary-grey">Empresa</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-primary-grey">Segmento</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-primary-grey">Origem</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-primary-grey">Cidade/UF</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-primary-grey">Responsável</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-primary-grey">Criado em</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline">
                    @forelse($this->leads as $lead)
                        <tr wire:key="{{ $lead->id }}" wire:click="openLeadDeal({{ $lead->id }})"
                            class="cursor-pointer transition-colors hover:bg-bg-light">
                            <td class="px-4 py-3">
                                <p class="font-medium text-primary-dark">{{ $lead->name }}</p>
                                <p class="text-xs text-primary-grey">{{ $lead->email }}</p>
                            </td>
                            <td class="px-4 py-3 text-primary-grey">{{ $lead->company ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if($lead->segment)
                                    <span class="inline-flex rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">{{ $lead->segment->label() }}</span>
                                @else
                                    <span class="text-primary-grey">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($lead->source)
                                    <span class="inline-flex rounded-full bg-secondary-purple/10 px-2 py-0.5 text-xs font-medium text-secondary-purple">{{ $lead->source->label() }}</span>
                                @else
                                    <span class="text-primary-grey">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-primary-grey">
                                {{ collect([$lead->city, $lead->state])->filter()->implode('/') ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-primary-grey">{{ $lead->owner->name }}</td>
                            <td class="px-4 py-3 text-primary-grey">{{ $lead->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-primary-grey">
                                Nenhum lead encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->leads->hasPages())
            <div class="border-t border-outline px-4 py-3">
                {{ $this->leads->links() }}
            </div>
        @endif
    </div>

    {{-- Create Lead Modal --}}
    @if($showCreateLeadModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-data>
            <div class="absolute inset-0 bg-primary-dark/50" wire:click="closeCreateLeadModal"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-bg-white p-6 shadow-xl mx-4">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-primary-dark">Novo Lead</h2>
                    <button wire:click="closeCreateLeadModal" class="text-primary-grey hover:text-primary-dark">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="createLead" class="space-y-4">
                    {{-- Step 1: email lookup --}}
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <x-input label="E-mail" type="email" wire:model="form.email" placeholder="lead@empresa.com" />
                        </div>
                        <div class="flex items-end">
                            <x-button type="button" variant="outline" wire:click="searchLead">Buscar</x-button>
                        </div>
                    </div>

                    @if($leadSearched && $leadFound)
                        <div class="rounded-lg bg-secondary-green/10 px-3 py-2 text-xs text-secondary-green">
                            Lead já cadastrado — preenchemos os dados. Informe apenas o negócio.
                        </div>
                    @endif

                    @if($leadSearched && !$leadFound)
                        <x-input label="Nome" wire:model="form.name" placeholder="Nome completo" />
                        <x-input label="Empresa" wire:model="form.company" placeholder="Nome da empresa" />
                        <x-input label="Telefone" wire:model="form.phone" placeholder="+55 11 99999-9999" />

                        <div class="grid grid-cols-2 gap-3">
                            <x-input label="Cidade" wire:model="form.city" placeholder="São Paulo" />
                            <x-input label="UF" wire:model="form.state" placeholder="SP" maxlength="2" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <x-select label="Segmento" wire:model="form.segment" placeholder="Selecione">
                                @foreach(LeadSegment::cases() as $case)
                                    <option value="{{ $case->value }}" @selected($form->segment === $case->value)>{{ $case->label() }}</option>
                                @endforeach
                            </x-select>
                            <x-select label="Origem" wire:model="form.source" placeholder="Selecione">
                                @foreach(LeadSource::cases() as $case)
                                    <option value="{{ $case->value }}" @selected($form->source === $case->value)>{{ $case->label() }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    @endif

                    @if($leadSearched)
                        <div class="border-t border-outline pt-4">
                            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-primary-grey">Negócio</p>
                            <x-input label="Título do negócio" wire:model="form.deal_title" placeholder="Ex: Mapeamento GPR – Obra Norte" />
                            <x-input label="Valor estimado (R$)" type="number" wire:model="form.deal_value" step="0.01" min="0.01" placeholder="0,00" class="mt-3" />
                        </div>

                        <div class="flex gap-3 pt-2">
                            <x-button type="submit">Criar Lead & Negócio</x-button>
                            <x-button type="button" variant="outline" wire:click="closeCreateLeadModal">Cancelar</x-button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    @endif
</div>