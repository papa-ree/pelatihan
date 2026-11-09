<?php

namespace Bale\Pelatihan\Support;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Encryption\Encrypter as LaravelEncrypter;
use RuntimeException;

/**
 * Enkripsi data pribadi (NIK, WhatsApp) memakai kunci terpisah dari APP_KEY.
 *
 * Nilai `PELATIHAN_DATA_KEY` harus identik di bale-dev dan bale-front, karena
 * kedua aplikasi membaca/menulis baris yang sama.
 */
final class PelatihanCrypto
{
    private static ?Encrypter $encrypter = null;

    public static function encrypter(): Encrypter
    {
        if (self::$encrypter instanceof Encrypter) {
            return self::$encrypter;
        }

        $key = (string) config('pelatihan.data_key');

        if ($key === '') {
            throw new RuntimeException(
                'PELATIHAN_DATA_KEY belum diisi. Jalankan: php artisan pelatihan:install'
            );
        }

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return self::$encrypter = new LaravelEncrypter($key, 'AES-256-CBC');
    }

    /**
     * Hash untuk pencarian exact-match. Enkripsi Laravel tidak deterministik,
     * jadi NIK tidak bisa dicari langsung.
     */
    public static function hash(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $key = (string) config('pelatihan.data_key');

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return hash_hmac('sha256', $value, $key);
    }

    public static function reset(): void
    {
        self::$encrypter = null;
    }
}
