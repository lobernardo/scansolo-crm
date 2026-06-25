<?php

namespace App\Http\Controllers\Api;

use App\Enums\DealServiceType;
use App\Enums\LeadSource;
use App\Http\Controllers\Controller;
use App\Models\DealNote;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InboundLeadController extends Controller
{
    public function __invoke(Request $request, LeadService $leadService): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|size:2',
            'source' => ['nullable', Rule::enum(LeadSource::class)],
            'deal_title' => 'nullable|string|max:255',
            'deal_description' => 'nullable|string',
            'deal_area_m2' => 'nullable|numeric|min:0',
            'deal_service_type' => ['nullable', Rule::enum(DealServiceType::class)],
        ]);

        $contatadoStage = PipelineStage::where('slug', 'contatado')->first();
        $owner = auth()->user();
        $tenantId = $owner->tenant_id;

        $email = $validated['email'] ?? null;
        $dealTitle = $validated['deal_title'] ?? "Serviço GPR - {$validated['company']}";
        $source = isset($validated['source']) ? LeadSource::from($validated['source']) : null;

        $existingLead = $email
            ? Lead::where('email', $email)->where('tenant_id', $tenantId)->withoutGlobalScopes()->first()
            : null;

        if ($existingLead) {
            $deal = $leadService->createDealForExistingLead(
                lead: $existingLead,
                owner: $owner,
                dealTitle: $dealTitle,
                dealValue: '0',
            );
        } else {
            $placeholderEmail = $email ?? sprintf('api-%s@sem-email.internal', \Illuminate\Support\Str::uuid());

            $deal = $leadService->createWithDeal(
                owner: $owner,
                leadName: $validated['name'],
                leadEmail: $placeholderEmail,
                leadPhone: $validated['phone'] ?? null,
                dealTitle: $dealTitle,
                dealValue: '0',
                company: $validated['company'],
                city: $validated['city'] ?? null,
                state: $validated['state'] ?? null,
                source: $source,
            );
        }

        if ($contatadoStage) {
            $deal->update(['pipeline_stage_id' => $contatadoStage->id]);
        }

        $updates = array_filter([
            'description' => $validated['deal_description'] ?? null,
            'area_m2' => $validated['deal_area_m2'] ?? null,
            'service_type' => $validated['deal_service_type'] ?? null,
        ], fn ($v) => $v !== null);

        if ($updates) {
            $deal->update($updates);
        }

        $sourceLabel = $source?->label() ?? 'API';
        $noteBody = sprintf('Lead recebido via %s em %s.', $sourceLabel, now()->format('d/m/Y'));

        DealNote::create([
            'tenant_id' => $tenantId,
            'deal_id' => $deal->id,
            'user_id' => $owner->id,
            'body' => $noteBody,
        ]);

        return response()->json([
            'lead_id' => $deal->lead_id,
            'deal_id' => $deal->id,
            'stage' => 'contatado',
        ], 201);
    }
}
