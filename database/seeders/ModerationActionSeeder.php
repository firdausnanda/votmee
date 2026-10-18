<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\RankingPost;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModerationActionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $moderator = User::where('role', 'moderator')->first()
            ?? User::where('role', 'admin')->first()
            ?? User::first();

        if (! $moderator) {
            return;
        }

        $actions = [
            [
                'target_type' => 'comment',
                'action' => 'comment.hide',
                'reason' => 'Komentar mengandung bahasa kasar dan melanggar pedoman komunitas.',
            ],
            [
                'target_type' => 'report',
                'action' => 'report.resolved',
                'reason' => 'Laporan telah diverifikasi dan tindakan moderasi telah diterapkan.',
            ],
            [
                'target_type' => 'report',
                'action' => 'report.dismissed',
                'reason' => 'Setelah peninjauan, konten dinilai masih mematuhi kriteria dan tidak melanggar.',
            ],
            [
                'target_type' => 'object',
                'action' => 'object.review',
                'reason' => 'Verifikasi keaslian dan kesesuaian kategori objek rujukan resmi selesai.',
            ],
            [
                'target_type' => 'ranking_post',
                'action' => 'post.review',
                'reason' => 'Pemeriksaan kepatuhan kriteria perbandingan postingan ranking.',
            ],
        ];

        // Seed 15 realistic moderation transactions
        for ($i = 0; $i < 15; $i++) {
            $template = $actions[$i % count($actions)];
            $targetId = match ($template['target_type']) {
                'comment' => (string) (Comment::inRandomOrder()->value('id') ?? rand(1, 20)),
                'report' => (string) (Report::inRandomOrder()->value('id') ?? rand(1, 10)),
                'object' => (string) (MasterObject::inRandomOrder()->value('id') ?? '01JH0000000000000000000000'),
                default => (string) (RankingPost::inRandomOrder()->value('id') ?? '01JH0000000000000000000000'),
            };

            $createdAt = now('UTC')->subDays(rand(0, 10))->subHours(rand(1, 23));

            DB::table('moderation_actions')->insert([
                'actor_id' => $moderator->id,
                'target_type' => $template['target_type'],
                'target_id' => $targetId,
                'action' => $template['action'],
                'reason' => $template['reason'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
