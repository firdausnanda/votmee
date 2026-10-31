<?php

namespace App\Services\AutoRanking;

use App\Models\Category;
use App\Models\MasterObject;
use Illuminate\Support\Collection;

/**
 * Fallback that needs no external service: it lists the most supported candidates
 * in order, or invites the community to judge new ones when nobody has voted yet.
 */
class TemplateRankingGenerator implements RankingDraftGenerator
{
    public function name(): string
    {
        return 'template';
    }

    public function draft(Category $category, Collection $candidates, string $languageCode, array $recentTitles = []): ?array
    {
        $picked = $candidates->take((int) config('autopilot.rankings.max_entries'))->values();

        if ($picked->count() < (int) config('autopilot.rankings.min_entries')) {
            return null;
        }

        $isSupported = (int) $picked->first()->support_points > 0;
        $count = $picked->count();
        $isId = $languageCode !== 'en';

        $title = match (true) {
            $isSupported && $isId => "{$count} {$category->name} Paling Didukung Komunitas Votmee",
            $isSupported => "{$count} Most Supported {$category->name} on Votmee",
            $isId => "{$count} {$category->name} yang Perlu Kamu Nilai",
            default => "{$count} {$category->name} Waiting for Your Verdict",
        };

        $intro = match (true) {
            $isSupported && $isId => 'Disusun dari poin dukungan komunitas Votmee. Makin banyak dukungan, makin tinggi peringkatnya.',
            $isSupported => 'Ordered by the support points the Votmee community has given.',
            $isId => 'Belum ada yang memberi dukungan di kategori ini. Mana yang menurutmu paling pantas ada di puncak?',
            default => 'Nobody has backed these yet. Which one deserves the top spot?',
        };

        $lines = $picked->map(function (MasterObject $object, int $index) use ($isSupported, $isId): string {
            $position = $index + 1;
            $points = (int) $object->support_points;

            return $isSupported
                ? "{$position}. {$object->canonical_name} - ".($isId ? "{$points} poin dukungan" : "{$points} support points")
                : "{$position}. {$object->canonical_name}";
        });

        $outro = $isId
            ? 'Setuju dengan urutannya? Beri dukungan pada favoritmu atau buat versi rankingmu sendiri.'
            : 'Agree with the order? Back your favourite or publish your own version.';

        return [
            'title' => $title,
            'criteria' => $intro."\n\n".$lines->implode("\n")."\n\n".$outro,
            'object_ids' => $picked->pluck('id')->all(),
        ];
    }
}
