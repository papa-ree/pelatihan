<?php

namespace Bale\Pelatihan\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Pencarian tabel warga: NIK tersimpan terenkripsi, jadi 16 digit dicocokkan
 * lewat `nik_hash` (exact). Input lain tetap memakai LIKE seperti tabel biasa.
 */
class CitizenSearch
{
    /**
     * @param  array<int, string>  $searchable
     */
    public static function apply(Builder $builder, string $query, array $searchable): void
    {
        $query = trim($query);

        if (preg_match('/^\d{16}$/', $query) === 1) {
            $builder->where('nik_hash', PelatihanCrypto::hash($query));

            return;
        }

        if ($searchable === []) {
            return;
        }

        $builder->where(function (Builder $inner) use ($query, $searchable) {
            foreach ($searchable as $i => $column) {
                $method = $i === 0 ? 'where' : 'orWhere';
                $inner->{$method}($column, 'like', '%'.$query.'%');
            }
        });
    }
}
