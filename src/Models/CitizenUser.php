<?php

namespace Bale\Pelatihan\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;

/**
 * Baris `users` milik database tenant. Bukan model auth: admin yang login
 * berasal dari database landlord, sedangkan NIK/WhatsApp merujuk baris user
 * di tenant yang sama dengan database pelatihan.
 */
class CitizenUser extends Model
{
    use HasUuids;
    use Notifiable;
    use UsesTenantConnection;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'username',
        'email',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function profile(): HasOne
    {
        return $this->hasOne(CitizenProfile::class, 'user_uuid', 'uuid');
    }

    /**
     * Tujuan WhatsApp untuk WaraChannel. Nomor sudah didekripsi oleh cast.
     */
    public function toWara(): ?string
    {
        return $this->profile?->whatsapp;
    }
}
