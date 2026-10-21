<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\RankingPost;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\Content\CommunityContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReportSeeder extends Seeder
{
    /**
     * Seed reports that point at content which really deserves the complaint, so the moderation
     * queue shows a spam ad, a hostile comment, and a few that moderators rightly dismissed.
     */
    public function run(): void
    {
        if (Report::query()->exists()) {
            return;
        }

        $users = User::pluck('id', 'username');

        foreach (CommunityContent::reports() as $data) {
            $reporterId = $users[$data['reporter']] ?? null;
            [$targetId, $targetType, $since] = $this->target($data);

            if ($reporterId === null || $targetId === null) {
                continue;
            }

            $createdAt = $since->copy()->addHours($data['hours_after']);
            $createdAt = $createdAt->isFuture() ? now('UTC')->subHours(mt_rand(2, 20)) : $createdAt;

            (new Report)->forceFill([
                'reporter_id' => $reporterId,
                'created_by' => $reporterId,
                'updated_by' => $reporterId,
                'target_type' => $targetType,
                'target_id' => (string) $targetId,
                'reason' => $data['reason'],
                'status' => $data['status'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();
        }
    }

    /** @return array{0: string|int|null, 1: string, 2: Carbon} */
    private function target(array $data): array
    {
        if ($data['type'] === 'comment') {
            $post = RankingPost::where('title', $data['post'])->first();
            $comment = $post ? Comment::where('ranking_post_id', $post->id)->where('body', 'like', '%'.$data['match'].'%')->first() : null;

            return [$comment?->id, 'comment', $comment?->created_at ?? now('UTC')];
        }

        if ($data['type'] === 'ranking_post') {
            $post = RankingPost::where('title', $data['post'])->first();

            return [$post?->id, 'ranking_post', $post?->published_at ?? now('UTC')];
        }

        $object = MasterObject::where('canonical_name', $data['object'])->first();

        return [$object?->id, 'object', $object?->created_at ?? now('UTC')];
    }
}
