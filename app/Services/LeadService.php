<?php

namespace App\Services;

use App\Enums\LeadSegment;
use App\Enums\LeadSource;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use App\Notifications\LeadAssignedNotification;
use Illuminate\Support\Facades\DB;

class LeadService
{
    public function createWithDeal(
        User $owner,
        string $leadName,
        string $leadEmail,
        ?string $leadPhone,
        string $dealTitle,
        string $dealValue,
        ?string $company = null,
        ?string $city = null,
        ?string $state = null,
        ?LeadSegment $segment = null,
        ?LeadSource $source = null,
    ): Deal {
        return DB::transaction(function () use ($owner, $leadName, $leadEmail, $leadPhone, $dealTitle, $dealValue, $company, $city, $state, $segment, $source) {
            $lead = Lead::create([
                'user_id' => $owner->id,
                'name' => $leadName,
                'company' => $company,
                'email' => $leadEmail,
                'phone' => $leadPhone,
                'city' => $city,
                'state' => $state,
                'segment' => $segment,
                'source' => $source,
            ]);

            return $this->createDealForLead($lead, $owner, $dealTitle, $dealValue);
        });
    }

    public function createDealForExistingLead(
        Lead $lead,
        User $owner,
        string $dealTitle,
        string $dealValue,
    ): Deal {
        return $this->createDealForLead($lead, $owner, $dealTitle, $dealValue);
    }

    public function assignTo(Lead $lead, User $newOwner, User $assignedBy): void
    {
        DB::transaction(function () use ($lead, $newOwner) {
            $lead->update(['user_id' => $newOwner->id]);
            $lead->deals()->update(['user_id' => $newOwner->id]);
        });

        if ($newOwner->id !== $assignedBy->id) {
            $lead->load('deals');
            foreach ($lead->deals as $deal) {
                $newOwner->notify(new LeadAssignedNotification($lead, $deal));
            }
        }
    }

    private function createDealForLead(Lead $lead, User $owner, string $dealTitle, string $dealValue): Deal
    {
        $newLeadStage = PipelineStage::where('is_terminal', false)->orderBy('sort_order')->firstOrFail();

        return Deal::create([
            'lead_id' => $lead->id,
            'user_id' => $owner->id,
            'pipeline_stage_id' => $newLeadStage->id,
            'title' => $dealTitle,
            'value' => $dealValue,
            'sort_order' => 0,
        ]);
    }
}
