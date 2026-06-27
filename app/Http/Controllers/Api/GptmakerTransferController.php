<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\DealNote;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GptmakerTransferController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('GPT Maker transfer webhook received', $payload);

        // GPT Maker sends contact data on transfer events
        // Typical fields: phone, name, email, conversation_id, agent_id, summary
        $phone = $this->normalizePhone(
            $payload['phone'] ?? $payload['contact']['phone'] ?? null
        );

        $name    = $payload['name'] ?? $payload['contact']['name'] ?? null;
        $email   = $payload['email'] ?? $payload['contact']['email'] ?? null;
        $summary = $payload['summary'] ?? $payload['conversation_summary'] ?? null;

        // Try to locate the lead by phone or email
        $lead = null;

        if ($phone) {
            $lead = Lead::withoutGlobalScopes()
                ->where('phone', $phone)
                ->orWhere('phone', '+' . ltrim($phone, '+'))
                ->first();
        }

        if (! $lead && $email) {
            $lead = Lead::withoutGlobalScopes()
                ->where('email', $email)
                ->first();
        }

        if (! $lead) {
            Log::warning('GPT Maker transfer: lead not found', compact('phone', 'email', 'name'));

            return response()->json(['status' => 'ok', 'note' => 'lead not found']);
        }

        // Get the most recent open deal for this lead
        $deal = Deal::withoutGlobalScopes()
            ->where('lead_id', $lead->id)
            ->orderByDesc('created_at')
            ->first();

        if (! $deal) {
            return response()->json(['status' => 'ok', 'note' => 'no deal found']);
        }

        // Build the note body
        $noteLines = ['⚡ **Solicitação de atendimento humano via IA (GPT Maker)**'];

        if ($name) {
            $noteLines[] = "Cliente: {$name}";
        }

        if ($summary) {
            $noteLines[] = "Resumo da conversa: {$summary}";
        }

        $noteLines[] = 'Data: ' . now()->format('d/m/Y H:i');

        DealNote::create([
            'tenant_id' => $deal->tenant_id,
            'deal_id'   => $deal->id,
            'user_id'   => $deal->user_id,
            'body'      => implode("\n", $noteLines),
        ]);

        Log::info('GPT Maker transfer: note created', ['deal_id' => $deal->id, 'lead_id' => $lead->id]);

        return response()->json(['status' => 'ok', 'deal_id' => $deal->id]);
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        // Remove all non-digit characters except leading +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        // Ensure it starts with +
        if (! str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }

        return $cleaned;
    }
}
