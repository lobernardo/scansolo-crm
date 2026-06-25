<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Projetos')] class extends Component {};
?>

<div class="flex flex-col items-center justify-center py-24 text-center">
    <div class="flex size-16 items-center justify-center rounded-2xl bg-primary/10 mb-4">
        <svg class="size-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
        </svg>
    </div>
    <h2 class="text-xl font-semibold text-primary-dark">Projetos</h2>
    <p class="mt-2 text-sm text-primary-grey">Esta funcionalidade estará disponível em breve.</p>
</div>
