<?php

namespace App\Services;

use App\Models\Category;
use App\Models\MasterObject;
use App\Models\RankingPost;
use App\Models\SupportEvent;
use App\Models\User;
use App\Services\AutoRanking\GeminiRankingGenerator;
use App\Services\AutoRanking\RankingDraftGenerator;
use App\Services\AutoRanking\TemplateRankingGenerator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Keeps Votmee alive while members are quiet: the official account publishes
 * generated rankings, drafted by Gemini with a template fallback.
 */
class AutoRankingService
{
    public function __construct(
        private RankingService $rankings,
        private GeminiRankingGenerator $gemini,
        private TemplateRankingGenerator $template,
    ) {}

    /**
     * @return array{status: 'disabled'|'active'|'daily_limit'|'queue_full'|'dry_run'|'done', posts: list<RankingPost>, categories: list<string>}
     */
    public function run(bool $force = false, bool $dryRun = false): array
    {
        $config = config('autopilot.rankings');

        if (! $config['enabled']) {
            return ['status' => 'disabled', 'posts' => [], 'categories' => []];
        }

        if (! $force && ! $this->isQuiet()) {
            return ['status' => 'active', 'posts' => [], 'categories' => []];
        }

        $remaining = (int) $config['daily_max'] - $this->generatedToday();

        if ($remaining <= 0) {
            return ['status' => 'daily_limit', 'posts' => [], 'categories' => []];
        }

        if (! $config['auto_publish'] && RankingPost::query()->where('status', 'pending_review')->count() >= (int) $config['max_pending']) {
            return ['status' => 'queue_full', 'posts' => [], 'categories' => []];
        }

        $categories = $this->eligibleCategories();

        if ($dryRun) {
            return ['status' => 'dry_run', 'posts' => [], 'categories' => $categories->take($remaining)->pluck('name')->all()];
        }

        $official = User::official();
        $posts = [];

        foreach ($categories as $category) {
            if (count($posts) >= $remaining) {
                break;
            }

            $post = $this->generateFor($official, $category);

            if ($post !== null) {
                $posts[] = $post;
            }
        }

        return ['status' => 'done', 'posts' => $posts, 'categories' => array_map(fn (RankingPost $post): string => $post->category->name, $posts)];
    }

    /** Members, not the official account, have published nothing within the quiet window. */
    public function isQuiet(): bool
    {
        return ! RankingPost::query()
            ->where('status', 'published')
            ->where('published_at', '>=', now('UTC')->subDays((int) config('autopilot.rankings.quiet_days')))
            ->whereHas('creator', fn ($query) => $query->where('is_official', false))
            ->exists();
    }

    public function generateFor(User $official, Category $category): ?RankingPost
    {
        $languageCode = (string) config('autopilot.rankings.language_code');
        $candidates = $this->candidates($category);
        $recentTitles = RankingPost::query()->whereNotNull('generator')->where('category_id', $category->id)
            ->latest()->limit(10)->pluck('title')->all();

        foreach ([$this->gemini, $this->template] as $generator) {
            $draft = $generator->draft($category, $candidates, $languageCode, $recentTitles);

            if ($draft === null || ! $this->isUsable($draft, $candidates) || $this->isDuplicate($category, $draft['object_ids'])) {
                continue;
            }

            return $this->publish($official, $category, $draft, $generator, $languageCode);
        }

        return null;
    }

    /**
     * Verified objects of the category, most supported first.
     *
     * @return EloquentCollection<int, MasterObject>
     */
    public function candidates(Category $category): EloquentCollection
    {
        return MasterObject::query()
            ->select(['id', 'category_id', 'canonical_name', 'verified_at'])
            ->addSelect(['support_points' => SupportEvent::query()
                ->selectRaw('COALESCE(SUM(amount), 0)')
                ->whereColumn('support_events.object_id', 'objects.id')
                ->where('status', 'valid')])
            ->where('category_id', $category->id)
            ->where('status', 'verified')
            ->orderByDesc('support_points')
            ->orderByDesc('verified_at')
            ->orderBy('id')
            ->limit((int) config('autopilot.rankings.candidate_limit'))
            ->get();
    }

    /**
     * Categories with enough verified objects and no generated ranking inside the
     * cooldown, the longest-waiting first.
     *
     * @return EloquentCollection<int, Category>
     */
    private function eligibleCategories(): EloquentCollection
    {
        $config = config('autopilot.rankings');

        return Category::query()
            ->select('categories.*')
            ->addSelect(['last_generated_at' => RankingPost::query()
                ->selectRaw('MAX(created_at)')
                ->whereColumn('ranking_posts.category_id', 'categories.id')
                ->whereNotNull('generator')])
            ->whereRaw('(SELECT COUNT(*) FROM objects WHERE objects.category_id = categories.id AND objects.status = ?) >= ?', ['verified', (int) $config['min_entries']])
            ->whereDoesntHave('rankingPosts', fn ($query) => $query->whereNotNull('generator')
                ->where('created_at', '>=', now('UTC')->subDays((int) $config['category_cooldown_days'])))
            ->orderBy('last_generated_at')
            ->orderBy('categories.id')
            ->get();
    }

    private function generatedToday(): int
    {
        return RankingPost::query()
            ->whereNotNull('generator')
            ->where('created_at', '>=', now('Asia/Jakarta')->startOfDay()->utc())
            ->count();
    }

    /**
     * Generated drafts are checked against the same limits members face, using only candidate objects.
     *
     * @param  array{title: string, criteria: string, object_ids: list<string>}  $draft
     * @param  Collection<int, MasterObject>  $candidates
     */
    private function isUsable(array $draft, Collection $candidates): bool
    {
        $config = config('autopilot.rankings');
        $ids = $draft['object_ids'];
        $titleLength = mb_strlen($draft['title']);
        $criteriaLength = mb_strlen($draft['criteria']);

        return count($ids) >= (int) $config['min_entries']
            && count($ids) <= (int) $config['max_entries']
            && count($ids) === count(array_unique($ids))
            && array_diff($ids, $candidates->pluck('id')->all()) === []
            && $titleLength >= 5 && $titleLength <= 255
            && $criteriaLength >= 10 && $criteriaLength <= 3000;
    }

    /**
     * A generated ranking that repeats the exact set of objects of a recent one adds nothing new.
     *
     * @param  list<string>  $objectIds
     */
    private function isDuplicate(Category $category, array $objectIds): bool
    {
        $signature = collect($objectIds)->sort()->values()->all();

        return RankingPost::query()
            ->whereNotNull('generator')
            ->where('category_id', $category->id)
            ->where('created_at', '>=', now('UTC')->subDays((int) config('autopilot.rankings.duplicate_window_days')))
            ->with('entries:id,ranking_post_id,object_id,position')
            ->get(['id'])
            ->contains(fn (RankingPost $post): bool => $post->entries->pluck('object_id')->sort()->values()->all() === $signature);
    }

    /** @param  array{title: string, criteria: string, object_ids: list<string>}  $draft */
    private function publish(User $official, Category $category, array $draft, RankingDraftGenerator $generator, string $languageCode): ?RankingPost
    {
        try {
            return $this->rankings->publish($official, [
                'title' => $draft['title'],
                'criteria' => $draft['criteria'],
                'category_id' => $category->id,
                'language_code' => $languageCode,
                'region_code' => (string) config('autopilot.rankings.region_code'),
                'object_ids' => $draft['object_ids'],
            ], config('autopilot.rankings.auto_publish') ? 'published' : 'pending_review', $generator->name())->setRelation('category', $category);
        } catch (ValidationException) {
            return null;
        }
    }
}
