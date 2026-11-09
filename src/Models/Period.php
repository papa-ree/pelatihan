<?php

namespace Bale\Pelatihan\Models;

use Bale\Cms\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Period extends Model
{
    use HasUuids;
    use UsesTenantConnection;

    protected $table = 'pelatihan_periods';

    protected $fillable = [
        'year',
        'name',
        'description',
        'starts_at',
        'ends_at',
        'is_active',
        'ai_schedule',
        'last_ai_run_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
        'last_ai_run_at' => 'datetime',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'period_id');
    }

    public function clusters(): HasMany
    {
        return $this->hasMany(Cluster::class, 'period_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function active(): ?self
    {
        return static::query()->active()->latest('created_at')->first();
    }

    /**
     * Hanya boleh ada satu periode aktif. Menonaktifkan yang lain tanpa
     * menghapus submission lama.
     */
    public function activate(): void
    {
        $this->getConnection()->transaction(function () {
            static::query()->whereKeyNot($this->getKey())->update(['is_active' => false]);

            $this->forceFill(['is_active' => true])->save();
        });
    }
}
