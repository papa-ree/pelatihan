<?php

namespace Bale\Pelatihan\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Draft extends Model
{
    use HasUuids;
    use UsesTenantConnection;

    protected $table = 'pelatihan_drafts';

    protected $fillable = [
        'user_uuid',
        'period_id',
        'type',
        'step',
        'payload',
    ];

    protected $casts = [
        'step' => 'integer',
        'payload' => 'array',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }
}
