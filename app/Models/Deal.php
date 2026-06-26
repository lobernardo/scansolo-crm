<?php

namespace App\Models;

use App\Enums\DealServiceType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deal extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'user_id',
        'pipeline_stage_id',
        'title',
        'value',
        'service_type',
        'area_m2',
        'scheduled_date',
        'description',
        'loss_reason',
        'sort_order',
        'followup_1_sent_at',
        'followup_2_sent_at',
        'followup_3_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'area_m2' => 'decimal:2',
            'scheduled_date' => 'date',
            'service_type' => DealServiceType::class,
            'followup_1_sent_at' => 'datetime',
            'followup_2_sent_at' => 'datetime',
            'followup_3_sent_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pipelineStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(DealNote::class);
    }
}
