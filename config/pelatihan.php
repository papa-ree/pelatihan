<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kunci data pribadi
    |--------------------------------------------------------------------------
    |
    | Kunci khusus untuk enkripsi NIK dan nomor WhatsApp. Nilainya HARUS sama
    | dengan PELATIHAN_DATA_KEY di aplikasi bale-front, karena kedua aplikasi
    | menulis ke database tenant yang sama.
    |
    */
    'data_key' => env('PELATIHAN_DATA_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Analitik AI
    |--------------------------------------------------------------------------
    |
    | Kredensial Gemini disimpan di tabel pelatihan_settings (terenkripsi),
    | bukan di .env. Nilai di bawah hanya batas teknis proses.
    |
    */
    'ai' => [
        'endpoint' => env('PELATIHAN_GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
        'chunk_size' => (int) env('PELATIHAN_AI_CHUNK', 50),
        'timeout' => (int) env('PELATIHAN_AI_TIMEOUT', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifikasi
    |--------------------------------------------------------------------------
    */
    'notification' => [
        'wa_channel' => 'wara',
    ],

];
