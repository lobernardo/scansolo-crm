<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class CreateLeadForm extends Form
{
    #[Validate('required|email', as: 'e-mail')]
    public string $email = '';

    #[Validate('required|min:2', as: 'nome')]
    public string $name = '';

    #[Validate('required|min:2', as: 'empresa')]
    public string $company = '';

    #[Validate('nullable', as: 'telefone')]
    public string $phone = '';

    #[Validate('nullable|max:100', as: 'cidade')]
    public string $city = '';

    #[Validate('nullable|size:2', as: 'estado')]
    public string $state = '';

    #[Validate('nullable', as: 'segmento')]
    public string $segment = '';

    #[Validate('nullable', as: 'origem')]
    public string $source = '';

    #[Validate('required|min:2', as: 'título do negócio')]
    public string $deal_title = '';

    #[Validate('required|numeric|min:0.01', as: 'valor')]
    public string $deal_value = '';
}
