<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\CommentImage;
use App\Models\CommentMention;
use App\Models\Conversation;
use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\ObjectContentRevision;
use App\Models\PointLedger;
use App\Models\RankingPost;
use App\Models\Report;
use App\Models\SavedRanking;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AchievementService;
use App\Services\FeedService;
use App\Services\GamificationService;
use App\Services\LeaderboardService;
use App\Services\MessagingService;
use App\Services\ModerationService;
use App\Services\NotificationService;
use App\Services\ObjectMatchingService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CommunityController extends Controller
{
    public function home(Request $request, FeedService $feed, LeaderboardService $leaderboard): Response
    {
        $filters = $this->feedFilters($request);
        $locale = $this->locale($request);
        $isIndexable = $request->query() === [] && ! $request->routeIs('community.login', 'community.register');

        return Inertia::render('Community/Feed', [
            ...$this->shared($request),
            'seo' => app(SeoService::class)->page(
                $locale,
                '',
                $locale === 'id' ? 'Ranking pilihan komunitas' : 'Community rankings',
                $locale === 'id' ? 'Temukan ranking dan pilihan favorit komunitas Votmee.' : 'Discover rankings and community favorites on Votmee.',
                $isIndexable,
            ),
            'loginModal' => $request->routeIs('community.login'),
            'registerModal' => $request->routeIs('community.register'),
            'loginStatus' => session('status'),
            'googleAuthError' => session('google_auth_error'),
            'feed' => Inertia::scroll(fn () => $feed->paginate($filters, $request->user())->withQueryString()),
            'filters' => $filters,
            'leaders' => $leaderboard->leaders('weekly', 'support'),
        ]);
    }

    public function explore(Request $request, FeedService $feed, LeaderboardService $leaderboard, ObjectMatchingService $matching): Response
    {
        $filters = [...$this->feedFilters($request), 'sort' => 'latest'];
        $locale = $this->locale($request);
        $browsing = ! isset($filters['q']) && ! isset($filters['category_id']);
        $spotlight = $browsing && (int) $request->query('page', 1) === 1
            ? $feed->paginate([...$filters, 'sort' => 'popular', 'period' => 'week'], $request->user(), 1)->getCollection()->first()
            : null;

        return Inertia::render('Community/Explore', [
            ...$this->shared($request),
            'seo' => app(SeoService::class)->page(
                $locale,
                '/explore',
                $locale === 'id' ? 'Jelajahi ranking dan objek' : 'Explore rankings and objects',
                $locale === 'id' ? 'Jelajahi ranking, artikel, dan objek pilihan komunitas.' : 'Explore rankings, articles, and objects from the community.',
                $request->query() === [],
            ),
            'feed' => Inertia::scroll(fn () => $feed->paginate($filters, $request->user())->withQueryString()),
            'filters' => $filters,
            'spotlight' => $spotlight,
            'rising' => $browsing ? $leaderboard->rising() : [],
            'objects' => isset($filters['q']) ? $matching->search($filters['q']) : [],
            'leaders' => $leaderboard->leaders('weekly', 'support'),
        ]);
    }

    public function ranking(Request $request, string $locale, RankingPost $post, LeaderboardService $leaderboard): Response|RedirectResponse
    {
        abort_unless($post->status === 'published', 404);
        $seoService = app(SeoService::class);
        $contentLocale = $seoService->locale($post->language_code);

        if ($locale !== $contentLocale) {
            return redirect()->route('community.ranking', [$contentLocale, $post->slug], 301);
        }

        $post->load(['creator:id,name,username,avatar_path', 'category:id,name,slug', 'entries.object:id,canonical_name,slug,status,image_path']);
        $coverObject = $post->entries->first(fn ($entry) => $entry->object?->status === 'verified' && $entry->object?->image_path)?->object;
        $scores = DB::table('support_events')->where('status', 'valid')->whereIn('ranking_entry_id', $post->entries->pluck('id'))
            ->selectRaw("ranking_entry_id, SUM(amount) as points, COUNT(DISTINCT CASE WHEN source = 'support' THEN user_id END) as supporters, SUM(CASE WHEN source = 'quick_vote' THEN amount ELSE 0 END) as quick_points")
            ->groupBy('ranking_entry_id')->get()->keyBy('ranking_entry_id');
        $comments = DB::table('comments')->join('users', 'users.id', '=', 'comments.user_id')
            ->leftJoin('comment_likes', 'comment_likes.comment_id', '=', 'comments.id')
            ->where('comments.ranking_post_id', $post->id)->where('comments.status', 'visible')
            ->selectRaw('comments.id, comments.parent_id, comments.body, comments.created_at, users.name, users.username, COUNT(comment_likes.id) as likes_count')
            ->groupBy('comments.id', 'comments.parent_id', 'comments.body', 'comments.created_at', 'users.name', 'users.username')
            ->orderByDesc('comments.created_at')
            ->orderByDesc('comments.id')
            ->limit(50)
            ->get();

        $userId = $request->user()?->id;
        $likedCommentIds = $userId
            ? DB::table('comment_likes')
                ->where('user_id', $userId)
                ->whereIn('comment_id', $comments->pluck('id'))
                ->pluck('comment_id')
                ->flip()
                ->toArray()
            : [];

        $comments->transform(function ($c) use ($likedCommentIds) {
            $c->liked = isset($likedCommentIds[$c->id]);
            $c->likes_count = (int) $c->likes_count;

            return $c;
        });
        CommentImage::attachToComments($comments);
        CommentMention::attachToComments($comments);

        $totalCommentsCount = DB::table('comments')
            ->where('comments.ranking_post_id', $post->id)
            ->where('comments.status', 'visible')
            ->count();

        return Inertia::render('Community/Ranking', [
            ...$this->shared($request),
            'seo' => $seoService->page(
                $locale,
                '/ranking/'.$post->slug,
                $post->title,
                Str::limit($post->criteria, 160),
                true,
                [],
                $coverObject ? '/backend/objects/'.$coverObject->id.'/image?size=thumb' : null,
                [$seoService->breadcrumbs([
                    ['name' => 'Votmee', 'url' => $seoService->url($locale)],
                    ['name' => $post->title, 'url' => $seoService->url($locale, '/ranking/'.$post->slug)],
                ])],
            ),
            'post' => $post,
            'isSaved' => $request->user() !== null && SavedRanking::query()
                ->where('user_id', $request->user()->id)->where('ranking_post_id', $post->id)->exists(),
            'scores' => $scores,
            'comments' => $comments,
            'total_comments_count' => $totalCommentsCount,
            'leaders' => $leaderboard->leaders(),
        ]);
    }

    public function object(Request $request, string $locale, MasterObject $object, LeaderboardService $leaderboard): Response|RedirectResponse
    {
        if ($object->status === 'merged') {
            $target = MasterObject::findOrFail($object->merged_into_id);

            return redirect()->route('community.object', [$locale, $target->slug]);
        }

        abort_if($object->status === 'rejected' && $object->submitted_by !== $request->user()?->id, 404);
        $object->load('aliases');
        $object->setAttribute('translations', DB::table('object_translations')->where('object_id', $object->id)->get(['locale', 'name']));
        $objectIds = $object->mergedFamilyIds();
        $seoService = app(SeoService::class);
        $summary = $seoService->publishedSummary($object);
        $summary?->load('author:id,name,username');

        if ($summary && $locale !== $seoService->locale($summary->language_code)) {
            return redirect()->route('community.object', [$seoService->locale($summary->language_code), $object->slug], 301);
        }
        $articles = $object->status === 'verified'
            ? ObjectArticle::query()->whereIn('object_id', $objectIds)->whereNotNull('published_revision_id')
                ->with(['author:id,name,username', 'publishedRevision:id,title,body,body_format,language_code,published_at'])
                ->latest()->orderByDesc('id')->paginate(10, ['*'], 'articles_page')->withQueryString()
            : null;
        $articles?->getCollection()->each(function (ObjectArticle $article): void {
            $revision = $article->publishedRevision;

            if ($revision === null) {
                return;
            }

            $revision->setAttribute('excerpt', $this->contentExcerpt($revision));
            $revision->makeHidden(['body', 'body_format']);
        });
        $myRevisions = $request->user()
            ? ObjectContentRevision::query()->where('object_id', $object->id)->where('author_id', $request->user()->id)
                ->with('article:id,type,published_revision_id')->latest()->limit(20)->get()
            : collect();
        $myRevisions->each(function (ObjectContentRevision $revision): void {
            $revision->setAttribute('excerpt', $this->contentExcerpt($revision));
            $revision->makeHidden(['body', 'body_format', 'sources']);
        });
        $stats = DB::table('support_events')->where('object_id', $object->id)->where('status', 'valid')
            ->selectRaw("COALESCE(SUM(amount), 0) as points, COUNT(DISTINCT CASE WHEN source = 'support' THEN user_id END) as supporters")->first();
        $rankingSearchInput = $request->query('ranking_q', '');
        $rankingSearch = is_string($rankingSearchInput) ? Str::limit(trim($rankingSearchInput), 100, '') : '';
        $rankingSort = $request->query('ranking_sort') === 'oldest' ? 'oldest' : 'latest';
        $rankingsQuery = RankingPost::query()->where('status', 'published')
            ->whereHas('entries', fn ($query) => $query->where('object_id', $object->id));
        $rankingTotal = $rankingSearch !== '' ? (clone $rankingsQuery)->count() : null;

        if ($rankingSearch !== '') {
            $rankingsQuery->where(fn ($query) => $query
                ->where('title', 'like', '%'.$rankingSearch.'%')
                ->orWhereHas('creator', fn ($creatorQuery) => $creatorQuery->where('name', 'like', '%'.$rankingSearch.'%')));
        }

        $rankings = $rankingsQuery->with(['creator:id,name,username,avatar_path', 'category:id,name,slug'])
            ->orderBy('published_at', $rankingSort === 'oldest' ? 'asc' : 'desc')
            ->orderBy('id', $rankingSort === 'oldest' ? 'asc' : 'desc')
            ->paginate(10, ['id', 'creator_id', 'category_id', 'slug', 'title', 'language_code', 'published_at'], 'rankings_page')
            ->withQueryString();
        $rankingTotal ??= $rankings->total();

        return Inertia::render('Community/Object', [
            ...$this->shared($request),
            'seo' => $seoService->page(
                $locale,
                '/object/'.$object->slug,
                $object->translations->firstWhere('locale', $locale)?->name ?? $object->canonical_name,
                $summary ? $this->contentExcerpt($summary) : ($locale === 'id'
                    ? 'Lihat informasi dan ranking tentang '.$object->canonical_name.'.'
                    : 'Explore information and rankings about '.$object->canonical_name.'.'),
                $summary !== null && $request->query() === [],
                [],
                $object->image_path && $object->status === 'verified' ? '/backend/objects/'.$object->id.'/image?size=thumb' : null,
                $summary ? [$seoService->breadcrumbs([
                    ['name' => 'Votmee', 'url' => $seoService->url($locale)],
                    ['name' => $object->canonical_name, 'url' => $seoService->url($locale, '/object/'.$object->slug)],
                ])] : [],
            ),
            'object' => $object,
            'summary' => $summary,
            'articles' => $articles,
            'myRevisions' => $myRevisions,
            'reviewFeedback' => $object->submitted_by === $request->user()?->id
                ? DB::table('audit_logs')->where('target_type', 'object')->where('target_id', $object->id)
                    ->where('action', 'object.review')->latest('id')->value('details')
                : null,
            'stats' => $stats,
            'rankings' => $rankings,
            'rankingTotal' => $rankingTotal,
            'rankingFilters' => ['search' => $rankingSearch, 'sort' => $rankingSort],
            'leaders' => $leaderboard->leaders(),
        ]);
    }

    public function article(Request $request, string $locale, ObjectArticle $article): Response|RedirectResponse
    {
        $article->load(['author:id,name,username', 'object', 'publishedRevision']);
        $revision = $article->publishedRevision;

        abort_unless($revision?->status === 'published' && $revision->article_id === $article->id, 404);

        $object = $article->object;

        while ($object->status === 'merged') {
            $object = MasterObject::findOrFail($object->merged_into_id);
        }

        abort_unless($object->status === 'verified', 404);
        $seoService = app(SeoService::class);
        $contentLocale = $seoService->locale($revision->language_code);

        if ($locale !== $contentLocale) {
            return redirect()->route('community.article', [$contentLocale, $article->id], 301);
        }

        $articleUrl = $seoService->url($locale, '/article/'.$article->id);

        return Inertia::render('Community/Article', [
            ...$this->shared($request),
            'seo' => $seoService->page(
                $locale,
                '/article/'.$article->id,
                $revision->title,
                $this->contentExcerpt($revision),
                true,
                [],
                $object->image_path ? '/backend/objects/'.$object->id.'/image?size=thumb' : null,
                [[
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => $revision->title,
                    'mainEntityOfPage' => $articleUrl,
                    'datePublished' => $revision->published_at?->toIso8601String(),
                    'author' => ['@type' => 'Person', 'name' => $article->author?->name],
                ], $seoService->breadcrumbs([
                    ['name' => 'Votmee', 'url' => $seoService->url($locale)],
                    ['name' => $revision->title, 'url' => $articleUrl],
                ])],
            ),
            'article' => $article->only('id', 'type', 'author_id'),
            'author' => $article->author?->only('name', 'username'),
            'revision' => $revision->only('id', 'title', 'body', 'body_format', 'language_code', 'sources', 'published_at'),
            'object' => $object->only('slug', 'canonical_name'),
        ]);
    }

    public function create(Request $request, LeaderboardService $leaderboard): Response
    {
        return Inertia::render('Community/Create', [
            ...$this->shared($request),
            'leaders' => $leaderboard->leaders(),
            'myObjects' => MasterObject::query()->where('submitted_by', $request->user()->id)
                ->whereIn('status', ['pending', 'needs_revision', 'rejected'])->latest()
                ->get(['id', 'slug', 'canonical_name', 'status']),
        ]);
    }

    public function writeObjectArticle(Request $request, string $locale, MasterObject $object, LeaderboardService $leaderboard): Response
    {
        abort_unless($object->status === 'verified' && $request->user()->canParticipate(), 403);

        return Inertia::render('Community/ContentEditor', [
            ...$this->shared($request),
            'leaders' => $leaderboard->leaders(),
            'object' => $object->only('id', 'slug', 'canonical_name'),
            'mode' => 'article',
        ]);
    }

    public function editObjectSummary(Request $request, string $locale, MasterObject $object, LeaderboardService $leaderboard): Response
    {
        abort_unless($object->status === 'verified' && $request->user()->canParticipate(), 403);

        $draft = $object->contentRevisions()->whereNull('article_id')->where('author_id', $request->user()->id)
            ->whereIn('status', ['pending', 'needs_revision', 'rejected'])->latest()->first();
        $published = $object->contentRevisions()->whereNull('article_id')->where('status', 'published')->latest('published_at')->first();
        $reviewPending = $object->contentRevisions()->whereNull('article_id')->where('status', 'pending')->exists();

        return Inertia::render('Community/ContentEditor', [
            ...$this->shared($request),
            'leaders' => $leaderboard->leaders(),
            'object' => $object->only('id', 'slug', 'canonical_name'),
            'mode' => 'summary',
            'initial' => $draft ?? $published,
            'reviewPending' => $reviewPending,
        ]);
    }

    public function editObjectArticle(Request $request, string $locale, ObjectArticle $article, LeaderboardService $leaderboard): Response
    {
        abort_unless($article->author_id === $request->user()->id && $article->object->status === 'verified' && $request->user()->canParticipate(), 403);

        $initial = $article->revisions()->whereIn('status', ['pending', 'needs_revision', 'rejected'])->latest()->first()
            ?? $article->publishedRevision;

        return Inertia::render('Community/ContentEditor', [
            ...$this->shared($request),
            'leaders' => $leaderboard->leaders(),
            'object' => $article->object->only('id', 'slug', 'canonical_name'),
            'mode' => 'article_revision',
            'article' => $article->only('id', 'type'),
            'initial' => $initial,
            'reviewPending' => $article->revisions()->where('status', 'pending')->exists(),
        ]);
    }

    public function editObjectSubmission(Request $request, string $locale, MasterObject $object, LeaderboardService $leaderboard): Response
    {
        abort_unless($object->submitted_by === $request->user()->id && in_array($object->status, ['needs_revision', 'rejected'], true) && $request->user()->canParticipate(), 403);

        $initial = $object->contentRevisions()->whereNull('article_id')->where('author_id', $request->user()->id)->latest()->first();
        $feedback = DB::table('audit_logs')->where('target_type', 'object')->where('target_id', $object->id)
            ->where('action', 'object.review')->latest('id')->value('details');

        return Inertia::render('Community/ContentEditor', [
            ...$this->shared($request),
            'leaders' => $leaderboard->leaders(),
            'object' => $object->only('id', 'slug', 'canonical_name'),
            'mode' => 'submission',
            'initial' => $initial,
            'reviewFeedback' => $feedback ? json_decode($feedback, true) : null,
        ]);
    }

    public function profile(Request $request, string $locale, User $user, LeaderboardService $leaderboard, AchievementService $achievements): Response
    {
        $publishedRankingsQuery = RankingPost::query()
            ->where('creator_id', $user->id)
            ->where('status', 'published');

        $walletBalance = (int) (Wallet::query()->where('user_id', $user->id)->value('balance') ?? 0);

        $supportPointsReceived = (int) DB::table('support_events')
            ->join('ranking_entries', 'ranking_entries.id', '=', 'support_events.ranking_entry_id')
            ->join('ranking_posts', 'ranking_posts.id', '=', 'ranking_entries.ranking_post_id')
            ->where('ranking_posts.creator_id', $user->id)
            ->where('ranking_posts.status', 'published')
            ->where('support_events.status', 'valid')
            ->sum('support_events.amount');

        $scoresForPost = DB::table('support_events')
            ->join('ranking_entries', 'ranking_entries.id', '=', 'support_events.ranking_entry_id')
            ->whereColumn('ranking_entries.ranking_post_id', 'ranking_posts.id')
            ->where('support_events.status', 'valid');

        $rankings = $publishedRankingsQuery
            ->with([
                'category:id,name,slug',
                'entries.object:id,canonical_name,slug,status,image_path',
            ])
            ->withCount(['comments' => fn ($comments) => $comments->where('status', 'visible')])
            ->addSelect([
                'support_points' => (clone $scoresForPost)->selectRaw('COALESCE(SUM(support_events.amount), 0)'),
            ])
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Community/Profile', [
            ...$this->shared($request),
            'profile' => [
                ...$user->only('id', 'name', 'username', 'role', 'locale', 'region_code', 'avatar_url', 'cover_url'),
                'created_at' => $user->created_at?->toIso8601String(),
                'stats' => [
                    'support_points_received' => $supportPointsReceived,
                    'wallet_balance' => $walletBalance,
                ],
            ],
            'rankings' => $rankings,
            'achievements' => $achievements->progress($user),
            'hiddenAchievementsLocked' => $achievements->hiddenLockedCount($user),
            'trustedContributor' => $user->trusted_at !== null,
            'blockedByViewer' => $request->user() !== null && $request->user()->blocks()->where('blocked_id', $user->id)->exists(),
            'leaders' => $leaderboard->leaders(),
        ]);
    }

    public function messages(Request $request, string $locale, MessagingService $messaging, LeaderboardService $leaderboard, ?Conversation $conversation = null): Response
    {
        if ($conversation !== null) {
            $messaging->participantOrFail($conversation, $request->user());
        }

        return Inertia::render('Community/Messages', [
            ...$this->shared($request),
            'activeConversationId' => $conversation?->id,
            'pendingRequests' => $messaging->pendingRequestCount($request->user()),
            'bodyMax' => config('messaging.body_max'),
            'leaders' => $leaderboard->leaders('weekly', 'support'),
        ]);
    }

    public function hallOfFame(Request $request, LeaderboardService $leaderboard, AchievementService $achievements): Response
    {
        $month = $achievements->currentMonth();

        return Inertia::render('Community/HallOfFame', [
            ...$this->shared($request),
            'month' => $month->format('Y-m'),
            'board' => $achievements->hallOfFame($month),
            'thresholds' => config('gamification.seasonal'),
            'leaders' => $leaderboard->leaders(),
        ]);
    }

    public function saved(Request $request, FeedService $feed, LeaderboardService $leaderboard): Response
    {
        $filters = ['saved_by' => $request->user()->id, 'sort' => 'saved'];

        return Inertia::render('Community/Saved', [
            ...$this->shared($request),
            'feed' => Inertia::scroll(fn () => $feed->paginate($filters, $request->user())->withQueryString()),
            'savedCount' => SavedRanking::query()->where('user_id', $request->user()->id)
                ->whereHas('rankingPost', fn ($query) => $query->where('status', 'published'))->count(),
            'leaders' => $leaderboard->leaders('weekly', 'support'),
        ]);
    }

    public function notifications(Request $request, NotificationService $notifications, LeaderboardService $leaderboard): Response
    {
        return Inertia::render('Community/Notifications', [
            ...$this->shared($request),
            'notifications' => Inertia::scroll(fn () => $request->user()->notifications()->paginate(20)
                ->through(fn ($notification): array => $notifications->listing($notification))),
            'leaders' => $leaderboard->leaders('weekly', 'support'),
        ]);
    }

    public function wallet(Request $request, LeaderboardService $leaderboard, GamificationService $gamification): Response
    {
        $flow = in_array($request->query('flow'), ['incoming', 'outgoing'], true) ? $request->query('flow') : 'all';
        $ledger = PointLedger::query()->where('user_id', $request->user()->id)->latest();

        if ($flow === 'incoming') {
            $ledger->where('amount', '>', 0);
        } elseif ($flow === 'outgoing') {
            $ledger->where('amount', '<', 0);
        }

        return Inertia::render('Community/Wallet', [
            ...$this->shared($request),
            'balance' => Wallet::query()->where('user_id', $request->user()->id)->value('balance') ?? 0,
            'claimedToday' => PointLedger::query()->where('user_id', $request->user()->id)->where('type', 'daily_bonus')
                ->whereDate('claimed_on', now('Asia/Jakarta')->toDateString())->exists(),
            'flow' => $flow,
            'ledger' => $ledger->paginate(20)->withQueryString(),
            'leaders' => $leaderboard->leaders(),
            'streak' => $gamification->streak($request->user()),
            'missions' => $gamification->missions($request->user()),
            'pendingMissions' => $gamification->pendingMissions($request->user()),
            'summary' => $this->walletSummary($request->user()),
        ]);
    }

    /**
     * Lifetime points received and spent, plus points received this Jakarta month.
     *
     * @return array{earned: int, spent: int, earned_this_month: int}
     */
    private function walletSummary(User $user): array
    {
        $totals = PointLedger::query()
            ->where('user_id', $user->id)
            ->selectRaw('COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END), 0) as earned')
            ->selectRaw('COALESCE(SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END), 0) as spent')
            ->selectRaw('COALESCE(SUM(CASE WHEN amount > 0 AND created_at >= ? THEN amount ELSE 0 END), 0) as earned_this_month', [
                now('Asia/Jakarta')->startOfMonth()->utc(),
            ])
            ->first();

        return [
            'earned' => (int) $totals->earned,
            'spent' => (int) $totals->spent,
            'earned_this_month' => (int) $totals->earned_this_month,
        ];
    }

    public function admin(Request $request, LeaderboardService $leaderboard): Response
    {
        return Inertia::render('Community/Admin', [
            ...$this->shared($request),
            'leaders' => $leaderboard->leaders(),
            'objects' => MasterObject::query()->where(fn ($query) => $query->whereIn('status', ['pending', 'needs_revision'])
                ->orWhere(fn ($images) => $images->where('status', 'verified')->whereNotNull('pending_image_path')))
                ->with(['contentRevisions' => fn ($query) => $query->whereNull('article_id')->latest()])
                ->oldest()->paginate(20, ['*'], 'objects_page'),
            'contentQueue' => ObjectContentRevision::query()->where('status', 'pending')
                ->whereHas('object', fn ($query) => $query->where('status', 'verified'))
                ->with(['author:id,name,username', 'article:id,type', 'object:id,canonical_name,slug'])->oldest()->paginate(20, ['*'], 'content_page'),
            'reports' => app(ModerationService::class)->withReportedMessages(Report::query()->where('status', 'open')->oldest()->paginate(20, ['*'], 'reports_page')),
            'mergedObjects' => MasterObject::query()->where('status', 'merged')->latest()->limit(20)->get(['id', 'canonical_name', 'status']),
            'isAdmin' => $request->user()->can('administer'),
            'stats' => [
                'pendingObjects' => MasterObject::query()->where(fn ($query) => $query->whereIn('status', ['pending', 'needs_revision'])
                    ->orWhere(fn ($images) => $images->where('status', 'verified')->whereNotNull('pending_image_path')))->count(),
                'openReports' => Report::query()->where('status', 'open')->count(),
                'verifiedObjects' => MasterObject::query()->where('status', 'verified')->count(),
                'totalCategories' => Category::query()->count(),
                'totalMerged' => MasterObject::query()->where('status', 'merged')->count(),
            ],
        ]);
    }

    private function shared(Request $request): array
    {
        $locale = $this->locale($request);

        return [
            'locale' => $locale,
            'renderedAt' => now()->toIso8601String(),
            'seo' => app(SeoService::class)->page($locale, '', 'Votmee', '', false),
            'categories' => Category::query()->withCount(['rankingPosts' => fn ($query) => $query->where('status', 'published')])
                ->orderBy('name')->get(['id', 'name', 'slug']),
            'recentComments' => Comment::query()
                ->with([
                    'user:id,name,username,avatar_path',
                    'rankingPost:id,title,slug,language_code',
                ])
                ->where('status', 'visible')
                ->whereHas('rankingPost', fn ($query) => $query->where('status', 'published'))
                ->latest()
                ->limit(5)
                ->get(['id', 'ranking_post_id', 'user_id', 'body', 'created_at']),
        ];
    }

    private function contentExcerpt(ObjectContentRevision $revision): string
    {
        $content = $revision->body_format === 'markdown'
            ? Str::markdown($revision->body, ['html_input' => 'strip', 'allow_unsafe_links' => false])
            : $revision->body;
        $plainText = Str::squish(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return Str::limit($plainText, 180);
    }

    private function locale(Request $request): string
    {
        return $request->route('locale') ?? app()->getLocale();
    }

    private function feedFilters(Request $request): array
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'in:for_you,popular,latest'],
            'period' => ['nullable', Rule::in(array_keys(FeedService::POPULAR_PERIODS))],
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'region_code' => ['nullable', 'string', 'max:16'],
        ]);

        if (isset($filters['category'])) {
            $filters['category_id'] = Category::query()->where('slug', $filters['category'])->value('id');
        }

        unset($filters['category']);
        $filters['language_code'] = $this->locale($request);
        $filters['region_code'] = $filters['region_code'] ?? $request->user()?->region_code;
        $filters['sort'] = $filters['sort'] ?? 'for_you';
        $filters['period'] = $filters['period'] ?? 'week';

        return $filters;
    }
}
