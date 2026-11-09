<?php

namespace Bale\Pelatihan\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Bale\Pelatihan\Casts\PelatihanEncrypted;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasUuids;
    use UsesTenantConnection;

    protected $table = 'pelatihan_settings';

    protected $fillable = [
        'gemini_api_key',
        'gemini_model',
        'email_subject',
        'email_body',
        'wa_body',
    ];

    protected $casts = [
        'gemini_api_key' => PelatihanEncrypted::class,
    ];

    protected $hidden = [
        'gemini_api_key',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'gemini_model' => 'gemini-3.1-flash-lite',
        ]);
    }
}
