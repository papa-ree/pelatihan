<?php

namespace Bale\Pelatihan\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasUuids;
    use UsesTenantConnection;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $table = 'pelatihan_programs';

    protected $fillable = [
        'cluster_id',
        'year',
        'quarter',
        'name',
        'status',
        'published_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'quarter' => 'integer',
        'published_at' => 'datetime',
    ];

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class, 'cluster_id');
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'program_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }
}
