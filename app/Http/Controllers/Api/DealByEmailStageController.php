<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\DealNote;
use App\Models\Lead;
use App\Models\PipelineStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealByEmailStageController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'stage_slug' => 'required|string|exists:pipeline_stages,slug',
        ]);

        $owner = auth()->user();
        $tenantId = $owner->tenant_id;

        $lead = Lead::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('email', $validated['email'])
            ->first();

        if (! $lead) {
            return response()->json(['ok' => false, 'error' => 'Deal not found'], 404);
        }

        $deal = Deal::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('lead_id', $lead->id)
            ->latest()
            ->first();

        if (! $deal) {
            return response()->json(['ok' => false, 'error' => 'Deal not found'], 404);
        }

        $stage = PipelineStage::where('slug', $validated['stage_slug'])->first();
        $deal->update(['pipeline_stage_id' => $stage->id]);

        DealNote::create([
            'tenant_id' => $tenantId,
            'deal_id' => $deal->id,
            'user_id' => $owner->id,
            'body' => sprintf('Fase atualizada para "%s" via API.', $stage->name),
        ]);

        return response()->json([
            'ok' => true,
            'deal_id' => $deal->id,
            'stage' => $stage->slug,
        ]);
    }
}
