<?php

namespace Bale\Pelatihan\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Bale\Pelatihan\Casts\PelatihanEncrypted;
use Bale\Pelatihan\Casts\PelatihanNik;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitizenProfile extends Model
{
    use HasUuids;
    use UsesTenantConnection;

    protected $table = 'pelatihan_citizen_profiles';

    protected $fillable = [
        'user_uuid',
        'sso_subject',
        'nik',
        'whatsapp',
        'completed_at',
    ];

    protected $casts = [
        'nik' => PelatihanNik::class,
        'whatsapp' => PelatihanEncrypted::class,
        'completed_at' => 'datetime:d M Y H:i',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(CitizenUser::class, 'user_uuid', 'uuid');
    }

    /**
     * NIK tersamar untuk tampilan admin: 4 digit awal dan 4 digit akhir.
     */
    public function maskedNik(): ?string
    {
        $nik = $this->nik;

        if ($nik === null || strlen($nik) < 8) {
            return $nik;
        }

        return substr($nik, 0, 4).str_repeat('*', strlen($nik) - 8).substr($nik, -4);
    }
}
