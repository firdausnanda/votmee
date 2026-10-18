<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\RankingPost;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'user')->get();
        $posts = RankingPost::all();
        $comments = Comment::all();
        $objects = MasterObject::all();

        if ($users->isEmpty()) {
            return;
        }

        $reportReasons = [
            'ranking_post' => [
                'Topik perbandingan duplikat dengan ranking yang sudah ada sebelumnya.',
                'Kriteria yang dicantumkan mengandung informasi yang kurang akurat.',
                'Judul postingan mengandung unsur clickbait yang menyesatkan pemilih.',
            ],
            'comment' => [
                'Komentar mengandung tautan promosi tidak relevan dan spam berulang.',
                'Bahasa yang digunakan tidak sopan dan memicu perdebatan tidak sehat.',
                'Komentar keluar dari topik pembahasan kandidat ranking.',
            ],
            'object' => [
                'Spesifikasi atau nama canonical objek perlu direvisi agar sesuai rujukan resmi.',
                'Objek ini terindikasi duplikasi dengan kandidat lain di kategori yang sama.',
                'Kategori objek kurang tepat dan perlu dipindahkan.',
            ],
        ];

        $statuses = ['open', 'open', 'resolved', 'dismissed'];

        // 1. Seed ranking_post reports
        if ($posts->isNotEmpty()) {
            foreach ($posts->random(min(3, $posts->count())) as $post) {
                $reporter = $users->random();
                $createdAt = now('UTC')->subDays(rand(1, 10))->subHours(rand(1, 20));

                Report::create([
                    'reporter_id' => $reporter->id,
                    'created_by' => $reporter->id,
                    'updated_by' => $reporter->id,
                    'target_type' => 'ranking_post',
                    'target_id' => $post->id,
                    'reason' => fake()->randomElement($reportReasons['ranking_post']),
                    'status' => fake()->randomElement($statuses),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }

        // 2. Seed comment reports
        if ($comments->isNotEmpty()) {
            foreach ($comments->random(min(4, $comments->count())) as $comment) {
                $reporter = $users->random();
                $createdAt = now('UTC')->subDays(rand(1, 8))->subHours(rand(1, 18));

                Report::create([
                    'reporter_id' => $reporter->id,
                    'created_by' => $reporter->id,
                    'updated_by' => $reporter->id,
                    'target_type' => 'comment',
                    'target_id' => (string) $comment->id,
                    'reason' => fake()->randomElement($reportReasons['comment']),
                    'status' => fake()->randomElement($statuses),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }

        // 3. Seed object reports
        if ($objects->isNotEmpty()) {
            foreach ($objects->random(min(3, $objects->count())) as $object) {
                $reporter = $users->random();
                $createdAt = now('UTC')->subDays(rand(1, 12))->subHours(rand(1, 15));

                Report::create([
                    'reporter_id' => $reporter->id,
                    'created_by' => $reporter->id,
                    'updated_by' => $reporter->id,
                    'target_type' => 'object',
                    'target_id' => $object->id,
                    'reason' => fake()->randomElement($reportReasons['object']),
                    'status' => fake()->randomElement($statuses),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }
}
