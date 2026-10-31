<?php

namespace App\Services\AutoRanking;

use App\Models\Category;
use App\Models\MasterObject;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiRankingGenerator implements RankingDraftGenerator
{
    public const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    public function name(): string
    {
        return 'gemini';
    }

    /**
     * Only object names and the category are sent; no member data ever leaves Votmee.
     * The response schema limits `object_ids` to the candidate IDs, and the caller
     * validates the draft again before publishing.
     */
    public function draft(Category $category, Collection $candidates, string $languageCode, array $recentTitles = []): ?array
    {
        $key = config('services.gemini.key');

        if (blank($key) || $candidates->isEmpty()) {
            return null;
        }

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $key])
                ->acceptJson()
                ->timeout((int) config('services.gemini.timeout'))
                ->post(sprintf(self::ENDPOINT, config('services.gemini.model')), [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $this->prompt($category, $candidates, $languageCode, $recentTitles)]]]],
                    'generationConfig' => [
                        'temperature' => 0.9,
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $this->schema($candidates),
                    ],
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Gemini ranking draft could not connect.', ['message' => $exception->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('Gemini ranking draft failed.', ['status' => $response->status()]);

            return null;
        }

        $draft = json_decode((string) $response->json('candidates.0.content.parts.0.text'), true);

        if (! is_array($draft) || ! is_string($draft['title'] ?? null) || ! is_string($draft['criteria'] ?? null) || ! is_array($draft['object_ids'] ?? null)) {
            Log::warning('Gemini ranking draft returned an unreadable response.');

            return null;
        }

        return [
            'title' => trim($draft['title']),
            'criteria' => trim($draft['criteria']),
            'object_ids' => array_values(array_filter($draft['object_ids'], 'is_string')),
        ];
    }

    /**
     * @param  Collection<int, MasterObject>  $candidates
     * @param  list<string>  $recentTitles
     */
    private function prompt(Category $category, Collection $candidates, string $languageCode, array $recentTitles): string
    {
        $language = $languageCode === 'en' ? 'English' : 'Bahasa Indonesia (santai, natural, tidak kaku)';
        $minimum = (int) config('autopilot.rankings.min_entries');
        $maximum = (int) config('autopilot.rankings.max_entries');
        $list = $candidates->map(fn (MasterObject $object): string => "- {$object->id} | {$object->canonical_name}")->implode("\n");
        $avoid = $recentTitles === [] ? '-' : collect($recentTitles)->map(fn (string $title): string => "- {$title}")->implode("\n");

        return <<<PROMPT
        Kamu adalah editor resmi Votmee, platform tempat komunitas membuat dan mendukung ranking.
        Buat SATU ranking editorial untuk kategori "{$category->name}" dalam {$language}.

        Aturan:
        1. Pilih {$minimum} sampai {$maximum} objek HANYA dari daftar kandidat di bawah, urutkan dari peringkat 1. Isi object_ids dengan ID persis dari daftar.
        2. Pilih satu sudut pandang yang spesifik dan menarik (misalnya untuk momen, kebutuhan, atau selera tertentu), bukan sekadar "terbaik".
        3. title: 5 sampai 120 karakter, menarik, tanpa emoji, jangan mirip judul yang sudah ada.
        4. criteria: teks biasa tanpa markdown, maksimal 2500 karakter. Awali dengan 1 sampai 2 kalimat kriteria penilaian, lalu satu baris alasan singkat per peringkat dengan format "1. Nama - alasan".
        5. Ini opini editorial. Jangan menyebut harga, angka, kandungan, alamat, atau fakta lain yang tidak bisa dipastikan. Jangan mengaku pernah mencoba atau mengalami sendiri.
        6. Akhiri criteria dengan satu kalimat yang mengajak komunitas memberi dukungan atau membuat versi ranking mereka sendiri.

        Kandidat (ID | Nama):
        {$list}

        Judul yang sudah pernah dipakai:
        {$avoid}
        PROMPT;
    }

    /**
     * @param  Collection<int, MasterObject>  $candidates
     * @return array<string, mixed>
     */
    private function schema(Collection $candidates): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'criteria' => ['type' => 'STRING'],
                'object_ids' => [
                    'type' => 'ARRAY',
                    'items' => ['type' => 'STRING', 'enum' => $candidates->pluck('id')->values()->all()],
                    'minItems' => (int) config('autopilot.rankings.min_entries'),
                    'maxItems' => (int) config('autopilot.rankings.max_entries'),
                ],
            ],
            'required' => ['title', 'criteria', 'object_ids'],
            'propertyOrdering' => ['title', 'object_ids', 'criteria'],
        ];
    }
}
