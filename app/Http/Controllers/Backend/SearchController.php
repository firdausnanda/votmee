<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\MasterObject;
use App\Models\RankingPost;
use App\Models\User;
use App\Services\ObjectMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Lightweight suggestions for the header search menu.
     */
    public function __invoke(Request $request, ObjectMatchingService $matching): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
        ]);
        $search = trim($data['q']);
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
        $usersOnly = str_starts_with($search, '@');

        $rankings = $usersOnly ? collect() : RankingPost::query()
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereRaw("title LIKE ? ESCAPE '!'", [$like])->orWhereRaw("criteria LIKE ? ESCAPE '!'", [$like]))
            ->with('category:id,name,slug')
            ->select(['id', 'title', 'slug', 'criteria', 'category_id'])
            ->addSelect([
                'supporters_count' => DB::table('support_events')
                    ->join('ranking_entries', 'ranking_entries.id', '=', 'support_events.ranking_entry_id')
                    ->whereColumn('ranking_entries.ranking_post_id', 'ranking_posts.id')
                    ->where('support_events.status', 'valid')
                    ->where('support_events.source', 'support')
                    ->selectRaw('COUNT(DISTINCT support_events.user_id)'),
            ])
            ->orderByDesc('supporters_count')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get()
            ->map(fn (RankingPost $post): array => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'criteria' => $post->criteria,
                'category' => $post->category?->name,
                'supporters_count' => (int) $post->supporters_count,
            ]);

        $objects = $usersOnly ? collect() : $matching->search($search)
            ->sortBy(fn (MasterObject $object): int => $object->status === 'verified' ? 0 : 1)
            ->take(4)
            ->map(fn (MasterObject $object): array => [
                'id' => $object->id,
                'name' => $object->canonical_name,
                'slug' => $object->slug,
                'image_path' => $object->image_path,
            ])
            ->values();

        $handle = ltrim($search, '@');
        $handlePrefix = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $handle).'%';
        $handleLike = '%'.$handlePrefix;

        $users = $handle === '' ? collect() : User::query()
            ->whereNotNull('username')
            ->where(fn ($query) => $query->whereRaw("username LIKE ? ESCAPE '!'", [$handleLike])->orWhereRaw("name LIKE ? ESCAPE '!'", [$handleLike]))
            ->select(['id', 'name', 'username', 'avatar_path'])
            ->addSelect([
                'rankings_count' => RankingPost::query()
                    ->whereColumn('creator_id', 'users.id')
                    ->where('status', 'published')
                    ->selectRaw('COUNT(*)'),
            ])
            ->orderByRaw("CASE WHEN username LIKE ? ESCAPE '!' OR name LIKE ? ESCAPE '!' THEN 0 ELSE 1 END", [$handlePrefix, $handlePrefix])
            ->orderByDesc('rankings_count')
            ->orderBy('username')
            ->limit(4)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
                'rankings_count' => (int) $user->rankings_count,
            ]);

        return response()->json([
            'rankings' => $rankings,
            'objects' => $objects,
            'users' => $users,
        ]);
    }
}
