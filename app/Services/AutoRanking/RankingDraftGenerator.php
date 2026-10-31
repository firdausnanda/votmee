<?php

namespace App\Services\AutoRanking;

use App\Models\Category;
use App\Models\MasterObject;
use Illuminate\Support\Collection;

interface RankingDraftGenerator
{
    /** Short identifier stored on `ranking_posts.generator`. */
    public function name(): string;

    /**
     * Draft a ranking from the given candidates, or null when no usable draft can be made.
     * Candidates arrive ordered by community support, strongest first.
     *
     * @param  Collection<int, MasterObject>  $candidates
     * @param  list<string>  $recentTitles
     * @return array{title: string, criteria: string, object_ids: list<string>}|null
     */
    public function draft(Category $category, Collection $candidates, string $languageCode, array $recentTitles = []): ?array;
}
