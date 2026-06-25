<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\DealNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DealNoteController extends Controller
{
    public function __invoke(Request $request, string $dealId): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'source' => ['required', Rule::in(['agent', 'manual'])],
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

        $note = DealNote::create([
            'tenant_id' => $tenantId,
            'deal_id' => $deal->id,
            'user_id' => $owner->id,
            'body' => $validated['content'],
        ]);

        return response()->json([
            'note_id' => $note->id,
            'created_at' => $note->created_at->toIso8601String(),
        ], 201);
    }
}
