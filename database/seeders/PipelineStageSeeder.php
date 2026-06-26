<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PipelineStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['name' => 'Novo Lead', 'slug' => 'novo_lead', 'sort_order' => 1, 'is_terminal' => false, 'is_won' => false],
            ['name' => 'Contatado', 'slug' => 'contatado', 'sort_order' => 2, 'is_terminal' => false, 'is_won' => false],
            ['name' => 'Visita Técnica', 'slug' => 'visita_tecnica', 'sort_order' => 3, 'is_terminal' => false, 'is_won' => false],
            ['name' => 'Proposta Enviada', 'slug' => 'proposta_enviada', 'sort_order' => 4, 'is_terminal' => false, 'is_won' => false],
            ['name' => 'Negociação', 'slug' => 'negociando', 'sort_order' => 5, 'is_terminal' => false, 'is_won' => false],
            ['name' => 'Ganho', 'slug' => 'fechado_ganho', 'sort_order' => 6, 'is_terminal' => true, 'is_won' => true],
            ['name' => 'Perdido', 'slug' => 'fechado_perdido', 'sort_order' => 7, 'is_terminal' => true, 'is_won' => false],
        ];

        foreach ($stages as $stage) {
            DB::table('pipeline_stages')->updateOrInsert(
                ['sort_order' => $stage['sort_order']],
                [...$stage, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
