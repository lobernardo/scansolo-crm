<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\DealNote;
use App\Models\PipelineStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealStageController extends Controller
{
    public function __invoke(Request $request, string $dealId): JsonResponse
    {
        $validated = $request->validate([
            'stage_slug' => 'required|string|exists:pipeline_stages,slug',
        ]);

        $owner = auth()->user();
        $tenantId = $owner->tenant_id;

        $deal = Deal::withoutGlobalScopes()
            ->where('id', $dealId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $deal) {
            return response()->json(['message' => 'Negócio não encontrado.'], 404);
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
            'deal_id' => $deal->id,
            'stage' => $stage->slug,
            'updated_at' => $deal->fresh()->updated_at->toIso8601String(),
        ]);
    }
}
