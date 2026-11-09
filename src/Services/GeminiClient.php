<?php

namespace Bale\Pelatihan\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien Google AI Studio (Gemini). Kredensial dibaca dari pelatihan_settings,
 * bukan .env, dan hanya dipakai di dalam proses analisis.
 */
class GeminiClient
{
    public function __construct(
        private string $apiKey,
        private string $model,
    ) {}

    /**
     * Kirim prompt dan kembalikan JSON yang sudah didekode.
     *
     * @return array<string, mixed>
     */
    public function json(string $prompt): array
    {
        $url = rtrim((string) config('pelatihan.ai.endpoint'), '/')
            .'/models/'.$this->model.':generateContent';

        $response = Http::timeout((int) config('pelatihan.ai.timeout', 120))
            ->withQueryParameters(['key' => $this->apiKey])
            ->post($url, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'responseMimeType' => 'application/json',
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini menolak permintaan (HTTP '.$response->status().').');
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Respons Gemini kosong.');
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            throw new RuntimeException('Respons Gemini bukan JSON valid.');
        }

        return $data;
    }
}
