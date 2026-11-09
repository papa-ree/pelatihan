<?php

namespace Bale\Pelatihan\Casts;

use Bale\Pelatihan\Support\PelatihanCrypto;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class PelatihanEncrypted implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return PelatihanCrypto::encrypter()->decryptString($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return PelatihanCrypto::encrypter()->encryptString((string) $value);
    }
}
