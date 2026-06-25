<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $renames = [
            1 => 'Novo Lead',
            2 => 'Contatado',
            3 => 'Visita Técnica',
            4 => 'Proposta Enviada',
            5 => 'Negociação',
            6 => 'Ganho',
            7 => 'Perdido',
        ];

        foreach ($renames as $sortOrder => $newName) {
            DB::table('pipeline_stages')
                ->where('sort_order', $sortOrder)
                ->update(['name' => $newName, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $restores = [
            1 => 'New Lead',
            2 => 'Contacted',
            3 => 'Qualified',
            4 => 'Proposal Sent',
            5 => 'Negotiation',
            6 => 'Won',
            7 => 'Lost',
        ];

        foreach ($restores as $sortOrder => $originalName) {
            DB::table('pipeline_stages')
                ->where('sort_order', $sortOrder)
                ->update(['name' => $originalName, 'updated_at' => now()]);
        }
    }
};
