<?php

namespace App\Console\Commands;

use App\Models\Deal;
use App\Models\PipelineStage;
use App\Services\WhatsappService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FollowupSendCommand extends Command
{
    protected $signature = 'followup:send';

    protected $description = 'Envia follow-ups automáticos para deals em Proposta Enviada';

    public function handle(): int
    {
        $stage = PipelineStage::where('slug', 'proposta_enviada')->first();

        if (! $stage) {
            $this->error('Stage proposta_enviada não encontrado.');

            return self::FAILURE;
        }

        $deals = Deal::withoutGlobalScopes()
            ->where('pipeline_stage_id', $stage->id)
            ->whereNotNull('lead_id')
            ->with('lead')
            ->get();

        $this->info("Verificando {$deals->count()} deal(s) em Proposta Enviada...");

        $evolutionUrl = config('services.evolution_api.base_url');
        $instance = config('services.evolution_api.instance');

        foreach ($deals as $deal) {
            $lead = $deal->lead;

            if (! $lead) {
                continue;
            }

            $daysSince = (int) $deal->updated_at->diffInDays(now());
            $name = $lead->name;

            if ($daysSince >= 2 && is_null($deal->followup_1_sent_at)) {
                $message = "Olá {$name}! Enviamos a proposta da ScanSOLO há 2 dias. Teve a chance de analisar? Ficamos à disposição para tirar dúvidas! 😊";
                $this->dispatch($deal, $lead->phone, $message, 'followup_1_sent_at', $evolutionUrl, $instance);
            } elseif ($daysSince >= 5 && is_null($deal->followup_2_sent_at)) {
                $message = "Oi {$name}, tudo bem? Estamos acompanhando sua proposta ScanSOLO. Há algo que possamos esclarecer sobre o serviço ou condições? 😊";
                $this->dispatch($deal, $lead->phone, $message, 'followup_2_sent_at', $evolutionUrl, $instance);
            } elseif ($daysSince >= 10 && is_null($deal->followup_3_sent_at)) {
                $message = "Olá {$name}! Passaram 10 dias desde que enviamos a proposta. Caso tenha interesse, ainda podemos ajustar condições. Qualquer dúvida, é só falar! 🙏";
                $this->dispatch($deal, $lead->phone, $message, 'followup_3_sent_at', $evolutionUrl, $instance);
            }
        }

        $this->info('Follow-ups processados com sucesso.');

        return self::SUCCESS;
    }

    private function dispatch(Deal $deal, ?string $phone, string $message, string $column, ?string $evolutionUrl, ?string $instance): void
    {
        if ($evolutionUrl && $instance && $phone) {
            try {
                WhatsappService::make()->sendMessage($instance, $phone, $message);
            } catch (\Throwable $e) {
                Log::error("followup:send falha no deal #{$deal->id}: {$e->getMessage()}");

                return;
            }
        } else {
            Log::info("followup:send [{$column}] deal #{$deal->id} phone={$phone} | {$message}");
        }

        DB::table('deals')->where('id', $deal->id)->update([$column => now()]);

        $this->line("  deal #{$deal->id}: {$column} enviado.");
    }
}
