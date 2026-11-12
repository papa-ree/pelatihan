<?php

namespace Bale\Pelatihan\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Penanda analisis AI sedang berjalan. Job tidak menyimpan status di database,
 * jadi satu nilai cache dipakai bersama oleh job, endpoint status, dan halaman.
 */
final class AnalysisState
{
    public static function key(string $periodId): string
    {
        return 'pelatihan:analysis:'.$periodId;
    }

    public static function start(string $periodId): void
    {
        // TTL membatasi tampilan "berjalan" bila worker mati di tengah job.
        Cache::put(self::key($periodId), now()->toIso8601String(), 3600);
    }

    public static function finish(string $periodId): void
    {
        Cache::forget(self::key($periodId));
    }

    public static function startedAt(string $periodId): ?string
    {
        $startedAt = Cache::get(self::key($periodId));

        return is_string($startedAt) ? $startedAt : null;
    }
}
