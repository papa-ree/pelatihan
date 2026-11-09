<?php

namespace Bale\Pelatihan\Casts;

use Bale\Pelatihan\Support\PelatihanCrypto;
use Illuminate\Database\Eloquent\Model;

/**
 * Sama seperti PelatihanEncrypted, tetapi sekaligus mengisi `nik_hash`
 * supaya admin bisa mencari NIK dengan exact match.
 */
class PelatihanNik extends PelatihanEncrypted
{
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        $plain = ($value === null || $value === '') ? null : (string) $value;

        return [
            $key => $plain === null ? null : PelatihanCrypto::encrypter()->encryptString($plain),
            'nik_hash' => PelatihanCrypto::hash($plain),
        ];
    }
}
