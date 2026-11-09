<?php

namespace Bale\Pelatihan\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasUuids;
    use UsesTenantConnection;

    protected $table = 'pelatihan_notification_logs';

    protected $fillable = [
        'program_id',
        'user_uuid',
        'channel',
        'status',
        'error',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(CitizenUser::class, 'user_uuid', 'uuid');
    }
}
