<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\Content\CommunityContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModerationActionSeeder extends Seeder
{
    /**
     * Record the moderator's work as a consequence of the data that exists: reports that were
     * resolved or dismissed, the comments hidden for it, and the objects they verified.
     */
    public function run(): void
    {
        $moderator = User::where('role', 'moderator')->first()
            ?? User::where('role', 'admin')->first()
            ?? User::first();

        if (! $moderator || DB::table('moderation_actions')->exists()) {
            return;
        }

        $record = fn (string $type, string $targetId, string $action, string $reason, $at) => DB::table('moderation_actions')->insert([
            'actor_id' => $moderator->id,
            'target_type' => $type,
            'target_id' => $targetId,
            'action' => $action,
            'reason' => $reason,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
        $resolutions = collect(CommunityContent::reports())->keyBy('reason');

        foreach (Report::whereIn('status', ['resolved', 'dismissed'])->orderBy('created_at')->get() as $report) {
            $reason = $resolutions[$report->reason]['resolution'] ?? 'Laporan sudah ditinjau oleh moderator.';
            $at = $report->created_at->copy()->addHours(mt_rand(2, 18));
            $at = $at->isFuture() ? now('UTC')->subMinutes(mt_rand(10, 120)) : $at;

            if ($report->status === 'resolved' && $report->target_type === 'comment') {
                $comment = Comment::find($report->target_id);

                if ($comment?->status === 'hidden') {
                    $record('comment', (string) $comment->id, 'comment.hide', $reason, $at);
                }
            }

            $record('report', (string) $report->id, 'report.'.$report->status, $reason, $at->copy()->addMinutes(2));
        }

        foreach (MasterObject::where('status', 'verified')->orderBy('created_at')->limit(6)->get() as $object) {
            $record('object', $object->id, 'object.review', 'Nama, kategori, dan identitas objek sudah sesuai dengan rujukan umum.', now('UTC')->subDays(mt_rand(10, 24))->subHours(mt_rand(0, 20)));
        }
    }
}
