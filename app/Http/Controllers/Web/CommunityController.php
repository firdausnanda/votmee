<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MasterObject;
use App\Models\PointLedger;
use App\Models\RankingPost;
use App\Models\Report;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FeedService;
use App\Services\LeaderboardService;
use App\Services\ObjectMatchingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CommunityController extends Controller
{
    public function home(Request $request, FeedService $feed, LeaderboardService $leaderboard): Response
    {
        $filters = $this->feedFilters($request);

        return Inertia::render('Community/Feed', [
            ...$this->shared($request),
            'feed' => $feed->paginate($filters)->withQueryString(),
            'filters' => $filters,
            'leaders' => $leaderboard->leaders(),
        ]);
    }

    public function explore(Request $request, FeedService $feed, LeaderboardService $leaderboard, ObjectMatchingService $matching): Response
    {
        $filters = $this->feedFilters($request);

        return Inertia::render('Community/Explore', [
            ...$this->shared($request),
            'feed' => $feed->paginate($filters)->withQueryString(),
            'filters' => $filters,
            'objects' => isset($filters['q']) ? $matching->search($filters['q'], $filters['category_id'] ?? null) : [],
            'leaders' => $leaderboard->leaders(),
        ]);
    }

    public function ranking(Request $request, string $locale, RankingPost $post, LeaderboardService $leaderboard): Response
    {
        abort_unless($post->status === 'published', 404);
        $post->load(['creator:id,name,username', 'category:id,name,slug', 'entries.object:id,canonical_name,slug,status']);
        $scores = DB::table('support_events')->where('status', 'valid')->whereIn('ranking_entry_id', $post->entries->pluck('id'))
            ->selectRaw('ranking_entry_id, SUM(amount) as points, COUNT(DISTINCT user_id) as supporters')
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

        $totalCommentsCount = DB::table('comments')
            ->where('comments.ranking_post_id', $post->id)
            ->where('comments.status', 'visible')
            ->count();

        return Inertia::render('Community/Ranking', [
            ...$this->shared($request),
            'post' => $post,
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

        abort_if($object->status === 'rejected', 404);
        $object->load('category', 'aliases');
        $object->setAttribute('translations', DB::table('object_translations')->where('object_id', $object->id)->get(['locale', 'name']));
        $stats = DB::table('support_events')->where('object_id', $object->id)->where('status', 'valid')
            ->selectRaw('COALESCE(SUM(amount), 0) as points, COUNT(DISTINCT user_id) as supporters')->first();
        $rankings = RankingPost::query()->where('status', 'published')->whereHas('entries', fn ($query) => $query->where('object_id', $object->id))
            ->with('creator:id,name,username')->latest('published_at')->limit(12)->get();

        return Inertia::render('Community/Object', [
            ...$this->shared($request),
            'object' => $object,
            'stats' => $stats,
            'rankings' => $rankings,
            'leaders' => $leaderboard->leaders(),
        ]);
    }

    public function create(Request $request, LeaderboardService $leaderboard): Response
    {
        return Inertia::render('Community/Create', [...$this->shared($request), 'leaders' => $leaderboard->leaders()]);
    }

    public function profile(Request $request, string $locale, User $user, LeaderboardService $leaderboard): Response
    {
        return Inertia::render('Community/Profile', [
            ...$this->shared($request),
            'profile' => $user->only('name', 'username', 'locale', 'region_code'),
            'rankings' => RankingPost::query()->where('creator_id', $user->id)->where('status', 'published')
                ->with('category:id,name,slug')->latest('published_at')->paginate(20),
            'leaders' => $leaderboard->leaders(),
        ]);
    }

    public function wallet(Request $request, LeaderboardService $leaderboard): Response
    {
        return Inertia::render('Community/Wallet', [
            ...$this->shared($request),
            'balance' => Wallet::query()->where('user_id', $request->user()->id)->value('balance') ?? 0,
            'ledger' => PointLedger::query()->where('user_id', $request->user()->id)->latest()->paginate(20),
            'leaders' => $leaderboard->leaders(),
        ]);
    }

    public function admin(Request $request, LeaderboardService $leaderboard): Response
    {
        return Inertia::render('Community/Admin', [
            ...$this->shared($request),
            'leaders' => $leaderboard->leaders(),
            'objects' => MasterObject::query()->whereIn('status', ['pending', 'needs_revision'])->oldest()->paginate(20, ['*'], 'objects_page'),
            'reports' => Report::query()->where('status', 'open')->oldest()->paginate(20, ['*'], 'reports_page'),
            'mergedObjects' => MasterObject::query()->where('status', 'merged')->latest()->limit(20)->get(['id', 'canonical_name', 'status', 'category_id']),
            'isAdmin' => $request->user()->can('administer'),
            'stats' => [
                'pendingObjects' => MasterObject::query()->whereIn('status', ['pending', 'needs_revision'])->count(),
                'openReports' => Report::query()->where('status', 'open')->count(),
                'verifiedObjects' => MasterObject::query()->where('status', 'verified')->count(),
                'totalCategories' => Category::query()->count(),
                'totalMerged' => MasterObject::query()->where('status', 'merged')->count(),
            ],
        ]);
    }

    private function shared(Request $request): array
    {
        return [
            'locale' => $this->locale($request),
            'categories' => Category::query()->withCount(['rankingPosts' => fn ($query) => $query->where('status', 'published')])
                ->orderBy('name')->get(['id', 'name', 'slug']),
        ];
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
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'region_code' => ['nullable', 'string', 'max:16'],
        ]);
        $filters['language_code'] = $this->locale($request);
        $filters['region_code'] = $filters['region_code'] ?? $request->user()?->region_code;
        $filters['sort'] = $filters['sort'] ?? 'for_you';

        return $filters;
    }
}
