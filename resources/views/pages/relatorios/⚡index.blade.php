<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Relatórios')] class extends Component {};
?>

<div class="flex flex-col items-center justify-center py-24 text-center">
    <div class="flex size-16 items-center justify-center rounded-2xl bg-primary/10 mb-4">
        <svg class="size-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
    </div>
    <h2 class="text-xl font-semibold text-primary-dark">Relatórios</h2>
    <p class="mt-2 text-sm text-primary-grey">Esta funcionalidade estará disponível em breve.</p>
</div>
