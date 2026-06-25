<?php

namespace App\Enums;

enum DealServiceType: string
{
    case MapeamentoGpr = 'mapeamento_gpr';
    case InvestigacaoSubsolo = 'investigacao_subsolo';
    case Batimetria = 'batimetria';
    case InspecaoViaria = 'inspecao_viaria';
    case DiagnosticoGeofisico = 'diagnostico_geofisico';
    case SondagemTunel = 'sondagem_tunel';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::MapeamentoGpr => 'Mapeamento GPR',
            self::InvestigacaoSubsolo => 'Investigação de Subsolo',
            self::Batimetria => 'Batimetria',
            self::InspecaoViaria => 'Inspeção Viária',
            self::DiagnosticoGeofisico => 'Diagnóstico Geofísico',
            self::SondagemTunel => 'Sondagem em Túnel',
            self::Outro => 'Outro',
        };
    }
}
