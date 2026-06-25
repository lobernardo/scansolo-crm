<?php

use App\Models\ApiToken;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Integração API')] class extends Component {
    public ?string $plainTextToken = null;

    public bool $hasToken = false;

    public ?string $tokenLastUsedAt = null;

    public bool $confirmRegenerate = false;

    public function mount(): void
    {
        $this->refreshTokenStatus();
    }

    public function generateToken(): void
    {
        $result = ApiToken::generateFor(auth()->user()->tenant_id);
        $this->plainTextToken = $result['plaintext'];
        $this->confirmRegenerate = false;
        $this->refreshTokenStatus();
    }

    public function askConfirmRegenerate(): void
    {
        $this->confirmRegenerate = true;
    }

    public function cancelRegenerate(): void
    {
        $this->confirmRegenerate = false;
    }

    private function refreshTokenStatus(): void
    {
        $token = ApiToken::where('tenant_id', auth()->user()->tenant_id)->first();
        $this->hasToken = $token !== null;
        $this->tokenLastUsedAt = $token?->last_used_at?->format('d/m/Y H:i');
    }
};
?>

<div class="max-w-3xl space-y-8">
    {{-- Settings sub-nav --}}
    <div class="flex gap-1 rounded-lg bg-bg-light p-1">
        <a href="{{ route('settings.whatsapp') }}" wire:navigate
           class="flex-1 rounded-md px-4 py-2 text-center text-sm font-medium text-primary-grey transition-colors hover:text-primary-dark {{ request()->routeIs('settings.whatsapp') ? 'bg-bg-white shadow-sm text-primary-dark' : '' }}">
            WhatsApp
        </a>
        <a href="{{ route('settings.api-integration') }}" wire:navigate
           class="flex-1 rounded-md px-4 py-2 text-center text-sm font-medium transition-colors {{ request()->routeIs('settings.api-integration') ? 'bg-bg-white shadow-sm text-primary-dark' : 'text-primary-grey hover:text-primary-dark' }}">
            Integração API
        </a>
    </div>

    {{-- API Token section --}}
    <div class="rounded-xl border border-outline bg-bg-white p-6 shadow-sm">
        <div class="mb-6">
            <h2 class="text-base font-semibold text-primary-dark">Token de acesso da API</h2>
            <p class="mt-1 text-sm text-primary-grey">Use este token para autenticar requisições do Make.com ou qualquer integração externa.</p>
        </div>

        {{-- Token revealed once --}}
        @if($plainTextToken)
            <div class="mb-6 rounded-lg border border-secondary-green/30 bg-secondary-green/10 p-4">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-secondary-green">Token gerado — copie agora</p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 break-all rounded bg-bg-light px-3 py-2 font-mono text-sm text-primary-dark">{{ $plainTextToken }}</code>
                    <button
                        onclick="navigator.clipboard.writeText('{{ $plainTextToken }}'); this.textContent = 'Copiado!';"
                        class="shrink-0 rounded-lg bg-secondary-green px-3 py-2 text-xs font-medium text-white hover:opacity-90">
                        Copiar
                    </button>
                </div>
                <p class="mt-2 text-xs text-secondary-green/80">Este token não será exibido novamente. Guarde em local seguro.</p>
            </div>
        @endif

        {{-- Token status --}}
        @if($hasToken && !$plainTextToken)
            <div class="mb-6 flex items-center gap-3 rounded-lg border border-outline bg-bg-light px-4 py-3">
                <div class="size-2 rounded-full bg-secondary-green"></div>
                <div>
                    <p class="text-sm font-medium text-primary-dark">Token ativo</p>
                    @if($tokenLastUsedAt)
                        <p class="text-xs text-primary-grey">Último uso: {{ $tokenLastUsedAt }}</p>
                    @else
                        <p class="text-xs text-primary-grey">Nunca utilizado</p>
                    @endif
                </div>
            </div>
        @elseif(!$hasToken)
            <div class="mb-6 flex items-center gap-3 rounded-lg border border-outline bg-bg-light px-4 py-3">
                <div class="size-2 rounded-full bg-primary-grey"></div>
                <p class="text-sm text-primary-grey">Nenhum token gerado ainda.</p>
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex gap-3">
            @if(!$hasToken)
                <x-button wire:click="generateToken">Gerar token</x-button>
            @elseif($confirmRegenerate)
                <div class="flex items-center gap-3 rounded-lg border border-secondary-red/30 bg-secondary-red/10 px-4 py-3 text-sm text-secondary-red">
                    <span>O token anterior será invalidado imediatamente. Confirmar?</span>
                    <x-button wire:click="generateToken" class="border-secondary-red bg-secondary-red text-white hover:opacity-90">Confirmar</x-button>
                    <x-button variant="outline" wire:click="cancelRegenerate">Cancelar</x-button>
                </div>
            @else
                <x-button variant="outline" wire:click="askConfirmRegenerate">Regenerar token</x-button>
            @endif
        </div>
    </div>

    {{-- Endpoints reference --}}
    <div class="rounded-xl border border-outline bg-bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-primary-dark">Endpoints disponíveis</h2>

        @php $baseUrl = config('app.url') . '/api'; @endphp

        <div class="space-y-4 text-sm">
            @foreach([
                ['POST', '/leads/inbound', 'Criar lead + negócio via Make.com'],
                ['PATCH', '/deals/{deal_id}/stage', 'Atualizar fase de um negócio'],
                ['POST', '/deals/{deal_id}/notes', 'Adicionar nota em um negócio'],
            ] as [$method, $path, $label])
                <div class="flex items-start gap-3 rounded-lg border border-outline p-3">
                    <span class="shrink-0 rounded bg-primary/10 px-2 py-0.5 font-mono text-xs font-bold text-primary">{{ $method }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-primary-grey">{{ $label }}</p>
                        <code class="mt-1 block break-all text-xs text-primary-dark">{{ $baseUrl }}{{ $path }}</code>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 rounded-lg bg-bg-light p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-primary-grey">Autenticação</p>
            <code class="mt-1 block text-xs text-primary-dark">Authorization: Bearer &lt;seu_token&gt;</code>
        </div>
    </div>
</div>
