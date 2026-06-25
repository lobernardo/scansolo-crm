<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class EditDealForm extends Form
{
    #[Validate('required|min:2', as: 'título')]
    public string $title = '';

    #[Validate('required|numeric|min:0.01', as: 'valor')]
    public string $value = '';

    #[Validate('nullable', as: 'tipo de serviço')]
    public string $service_type = '';

    #[Validate('nullable|numeric|min:0', as: 'área (m²)')]
    public string $area_m2 = '';

    #[Validate('nullable|date', as: 'data agendada')]
    public string $scheduled_date = '';

    #[Validate('nullable|max:2000', as: 'descrição')]
    public string $description = '';
}
