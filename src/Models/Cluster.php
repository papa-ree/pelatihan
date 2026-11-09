<?php

namespace Bale\Pelatihan\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cluster extends Model
{
    use HasUuids;
    use UsesTenantConnection;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_MERGED = 'merged';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'pelatihan_clusters';

    protected $fillable = [
        'period_id',
        'name',
        'sub_category',
        'keywords',
        'urgency_score',
        'summary',
        'recommendation',
        'status',
        'merged_into_id',
    ];

    protected $casts = [
        'keywords' => 'array',
        'urgency_score' => 'integer',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class, 'cluster_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'cluster_id');
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
}
