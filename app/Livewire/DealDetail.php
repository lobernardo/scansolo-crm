<?php

namespace App\Livewire;

use App\Enums\AccountStatus;
use App\Enums\ConnectionStatus;
use App\Enums\UserRole;
use App\Livewire\Forms\EditDealForm;
use App\Models\Deal;
use App\Models\DealNote;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\WhatsappMessage;
use App\Services\DealNoteService;
use App\Services\DealService;
use App\Services\LeadService;
use App\Services\WhatsappService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class DealDetail extends Component
{
    public ?int $dealId = null;

    public bool $showSlideOver = false;

    public string $activeTab = 'details';

    public EditDealForm $editForm;

    public bool $isEditing = false;

    public string $noteBody = '';

    public bool $showLossReasonModal = false;

    public string $lossReason = '';

    public string $assignLeadToUserId = '';

    public string $reassignDealToUserId = '';

    public array $whatsappMessages = [];

    public string $whatsappMessageText = '';

    public ?string $whatsappError = null;

    #[Computed]
    public function deal(): ?Deal
    {
        if (! $this->dealId) {
            return null;
        }

        return Deal::with(['lead', 'owner', 'pipelineStage', 'notes' => fn ($q) => $q->with('author')->latest()])
            ->find($this->dealId);
    }

    #[Computed]
    public function hasWhatsappConnection(): bool
    {
        $connection = auth()->user()->tenant->whatsappConnection;
        if (! $connection) {
            return false;
        }

        return $connection->whatsappConnectionStatus->name === ConnectionStatus::Connected->value;
    }

    #[Computed]
    public function salespersons()
    {
        $activeStatus = UserStatus::where('name', AccountStatus::Active->value)->firstOrFail();
        $spRole = Role::where('name', UserRole::Salesperson->value)->firstOrFail();

        return User::where('tenant_id', auth()->user()->tenant_id)
            ->where('user_status_id', $activeStatus->id)
            ->where('role_id', $spRole->id)
            ->get();
    }

    #[On('openDealDetail')]
    public function openDealDetail(int $dealId): void
    {
        $this->dealId = $dealId;
        unset($this->deal);

        $deal = $this->deal;
        if (! $deal) {
            return;
        }

        $this->authorize('view', $deal);
        $this->editForm->title = $deal->title;
        $this->editForm->value = (string) $deal->value;
        $this->editForm->service_type = $deal->service_type?->value ?? '';
        $this->editForm->area_m2 = $deal->area_m2 ? (string) $deal->area_m2 : '';
        $this->editForm->scheduled_date = $deal->scheduled_date?->format('Y-m-d') ?? '';
        $this->editForm->description = $deal->description ?? '';
        $this->showSlideOver = true;
        $this->activeTab = 'details';
        $this->isEditing = false;
        $this->noteBody = '';
        $this->lossReason = '';
        $this->showLossReasonModal = false;
        $this->assignLeadToUserId = '';
        $this->reassignDealToUserId = '';
        $this->whatsappMessages = [];
        $this->whatsappMessageText = '';
        $this->whatsappError = null;
    }

    public function closeDealDetail(): void
    {
        $this->showSlideOver = false;
        $this->dealId = null;
        unset($this->deal);
    }

    public function startEditing(): void
    {
        $this->authorize('update', $this->deal);
        $this->isEditing = true;
    }

    public function saveDeal(): void
    {
        $deal = $this->deal;
        $this->authorize('update', $deal);
        $this->editForm->validate();

        $deal->update([
            'title' => $this->editForm->title,
            'value' => $this->editForm->value,
            'service_type' => $this->editForm->service_type ?: null,
            'area_m2' => $this->editForm->area_m2 ?: null,
            'scheduled_date' => $this->editForm->scheduled_date ?: null,
            'description' => $this->editForm->description ?: null,
        ]);

        unset($this->deal);
        $this->isEditing = false;
        $this->dispatch('dealUpdated');
    }

    public function cancelEditing(): void
    {
        $deal = $this->deal;
        $this->editForm->title = $deal->title;
        $this->editForm->value = (string) $deal->value;
        $this->editForm->service_type = $deal->service_type?->value ?? '';
        $this->editForm->area_m2 = $deal->area_m2 ? (string) $deal->area_m2 : '';
        $this->editForm->scheduled_date = $deal->scheduled_date?->format('Y-m-d') ?? '';
        $this->editForm->description = $deal->description ?? '';
        $this->isEditing = false;
    }

    public function markAsWon(DealService $dealService): void
    {
        $deal = $this->deal;
        $this->authorize('update', $deal);
        $dealService->markAsWon($deal);
        unset($this->deal);
        $this->dispatch('dealUpdated');
    }

    public function openLossReasonModal(): void
    {
        $this->authorize('update', $this->deal);
        $this->showLossReasonModal = true;
    }

    public function markAsLost(DealService $dealService): void
    {
        $deal = $this->deal;
        $this->authorize('update', $deal);
        $this->validate([
            'lossReason' => 'required|min:2',
        ], [
            'lossReason.required' => 'O motivo da perda é obrigatório.',
            'lossReason.min' => 'O motivo da perda deve ter pelo menos 2 caracteres.',
        ]);

        $dealService->markAsLost($deal, $this->lossReason);
        unset($this->deal);
        $this->showLossReasonModal = false;
        $this->lossReason = '';
        $this->dispatch('dealUpdated');
    }

    public function addNote(DealNoteService $dealNoteService): void
    {
        $deal = $this->deal;
        $this->authorize('create', [DealNote::class, $deal]);
        $this->validate([
            'noteBody' => 'required|min:2',
        ], [
            'noteBody.required' => 'O texto da nota é obrigatório.',
            'noteBody.min' => 'A nota deve ter pelo menos 2 caracteres.',
        ]);

        $dealNoteService->create($deal, auth()->user(), $this->noteBody);
        $this->noteBody = '';
        unset($this->deal);
    }

    public function assignLead(LeadService $leadService): void
    {
        $this->authorize('assign', Lead::class);

        $this->validate([
            'assignLeadToUserId' => [
                'required',
                Rule::exists('users', 'id')->where('tenant_id', auth()->user()->tenant_id),
            ],
        ], [
            'assignLeadToUserId.required' => 'Selecione um vendedor.',
            'assignLeadToUserId.exists' => 'O vendedor selecionado é inválido.',
        ]);

        $newOwner = User::findOrFail($this->assignLeadToUserId);

        $leadService->assignTo($this->deal->lead, $newOwner, auth()->user());

        $this->assignLeadToUserId = '';
        unset($this->deal, $this->salespersons);
        $this->dispatch('dealUpdated');
    }

    public function reassignDeal(DealService $dealService): void
    {
        $this->authorize('assign', Deal::class);

        $this->validate([
            'reassignDealToUserId' => [
                'required',
                Rule::exists('users', 'id')->where('tenant_id', auth()->user()->tenant_id),
            ],
        ], [
            'reassignDealToUserId.required' => 'Selecione um vendedor.',
            'reassignDealToUserId.exists' => 'O vendedor selecionado é inválido.',
        ]);

        $newOwner = User::findOrFail($this->reassignDealToUserId);

        $dealService->reassign($this->deal, $newOwner, auth()->user());

        $this->reassignDealToUserId = '';
        unset($this->deal, $this->salespersons);
        $this->dispatch('dealUpdated');
    }

    public function loadWhatsappMessages(): void
    {
        $deal = $this->deal;
        if (! $deal || ! $this->hasWhatsappConnection) {
            return;
        }

        $this->authorize('view', $deal);

        $phone = $deal->lead->phone;
        if (! $phone) {
            $this->whatsappMessages = [];

            return;
        }

        $this->whatsappMessages = WhatsappMessage::where('lead_id', $deal->lead_id)
            ->orderBy('message_timestamp')
            ->get()
            ->map(fn (WhatsappMessage $msg) => [
                'id' => $msg->message_id,
                'fromMe' => $msg->from_me,
                'text' => $msg->body,
                'timestamp' => $msg->message_timestamp,
            ])
            ->toArray();

        $this->whatsappError = null;
    }

    public function sendWhatsappMessage(): void
    {
        $deal = $this->deal;
        $this->authorize('view', $deal);

        $this->validate([
            'whatsappMessageText' => 'required|min:1',
        ], [
            'whatsappMessageText.required' => 'A mensagem é obrigatória.',
        ]);

        $phone = $deal->lead->phone;
        if (! $phone) {
            $this->whatsappError = 'O lead não possui número de telefone cadastrado.';

            return;
        }

        try {
            $connection = auth()->user()->tenant->whatsappConnection;
            $service = WhatsappService::make();
            $result = $service->sendMessage($connection->instance_name, $phone, $this->whatsappMessageText);

            $sanitizedPhone = preg_replace('/\D/', '', $phone);

            WhatsappMessage::create([
                'tenant_id' => $connection->tenant_id,
                'whatsapp_connection_id' => $connection->id,
                'lead_id' => $deal->lead_id,
                'remote_jid' => $sanitizedPhone.'@s.whatsapp.net',
                'message_id' => $result['key']['id'] ?? null,
                'from_me' => true,
                'body' => $this->whatsappMessageText,
                'message_timestamp' => time(),
            ]);

            $this->whatsappMessages[] = [
                'id' => $result['key']['id'] ?? null,
                'fromMe' => true,
                'text' => $this->whatsappMessageText,
                'timestamp' => time(),
            ];

            $this->whatsappMessageText = '';
            $this->whatsappError = null;
        } catch (\Exception $e) {
            $this->whatsappError = 'Erro ao enviar mensagem.';
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;

        if ($tab === 'whatsapp') {
            $this->loadWhatsappMessages();
        }
    }

    public function render()
    {
        return view('livewire.deal-detail');
    }
}
