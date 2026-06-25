<?php

namespace App\Enums;

enum LeadSource: string
{
    case Whatsapp = 'whatsapp';
    case Site = 'site';
    case Indicacao = 'indicacao';
    case ProspeccaoAtiva = 'prospeccao_ativa';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::Whatsapp => 'WhatsApp',
            self::Site => 'Site',
            self::Indicacao => 'Indicação',
            self::ProspeccaoAtiva => 'Prospecção Ativa',
            self::Outro => 'Outro',
        };
    }
}
