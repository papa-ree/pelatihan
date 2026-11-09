<?php

namespace Bale\Pelatihan\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    use HasUuids;
    use UsesTenantConnection;

    public const STATUS_PENDING = 'Pending_AI_Processing';

    public const STATUS_PROCESSED = 'AI_Processed';

    public const STATUS_FAILED = 'AI_Failed';

    protected $table = 'pelatihan_submissions';

    protected $fillable = [
        'period_id',
        'cluster_id',
        'user_uuid',
        'type',
        'data',
        'status',
        'ai_error',
        'ai_processed_at',
        'submitted_at',
    ];

    protected $casts = [
        'data' => 'array',
        'ai_processed_at' => 'datetime',
        'submitted_at' => 'datetime:d M Y H:i',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class, 'cluster_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(CitizenUser::class, 'user_uuid', 'uuid');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CitizenProfile::class, 'user_uuid', 'user_uuid');
    }

    public function scopePendingAi(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
