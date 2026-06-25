<?php

namespace App\Enums;

enum LeadSegment: string
{
    case ConstrucaoCivil = 'construcao_civil';
    case Utilities = 'utilities';
    case Mineracao = 'mineracao';
    case Industrial = 'industrial';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::ConstrucaoCivil => 'Construção Civil',
            self::Utilities => 'Utilities',
            self::Mineracao => 'Mineração',
            self::Industrial => 'Industrial',
            self::Outro => 'Outro',
        };
    }
}
