<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\RankingPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $posts = RankingPost::all();

        if ($users->isEmpty() || $posts->isEmpty()) {
            return;
        }

        $sampleComments = [
            'Pilihan nomor satu memang layak memimpin, saya sudah gunakan selama beberapa bulan dan performanya sangat stabil.',
            'Kriteria pembandingannya sangat jelas dan adil. Sangat membantu yang sedang bingung memilih.',
            'Kandidat nomor dua menurut saya punya keunggulan tersendiri di efisiensi daya dan harga yang lebih terjangkau.',
            'Kamera dan hasil fotonya jernih sekali, apalagi untuk foto low light di malam hari.',
            'Kualitas material dan finishing produk ini benar-benar terasa premium di tangan.',
            'Menarik melihat persaingan ketat di kategori ini. Semoga segera ada update untuk generasi berikutnya.',
            'Saya lebih condong ke pilihan ketiga karena faktor kenyamanan dan layanan purna jualnya yang luas.',
            'Lanskap alamnya luar biasa indah dan wajib dikunjungi setidaknya sekali seumur hidup.',
            'Rasa bumbu rempahnya sangat otentik dan meresap sampai ke serat daging terdalam.',
            'Suspensi dan kenyamanan kabinnya sangat tenang, cocok sekali untuk perjalanan jauh bersama keluarga.',
        ];

        $sampleReplies = [
            'Setuju banget dengan ulasan ini!',
            'Bener, daya tahannya memang terbukti paling awet dibanding yang lain.',
            'Kira-kira worth it nggak kalau upgrade dari seri sebelumnya?',
            'Terima kasih atas insight-nya, sangat membantu pertimbangan saya.',
            'Sama, pengalaman saya juga persis seperti yang disampaikan.',
        ];

        foreach ($posts as $post) {
            $numComments = rand(4, 9);
            $postVoters = $users->random(min($numComments, $users->count()));

            foreach ($postVoters as $idx => $user) {
                $body = fake()->boolean(70)
                    ? fake()->randomElement($sampleComments)
                    : fake('id_ID')->paragraph(2);

                $createdAt = now('UTC')->subDays(rand(0, 12))->subHours(rand(1, 23));
                $status = fake()->boolean(96) ? 'visible' : 'hidden';

                $comment = Comment::create([
                    'ranking_post_id' => $post->id,
                    'user_id' => $user->id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'parent_id' => null,
                    'body' => $body,
                    'status' => $status,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // 40% chance of a reply to this comment
                if (fake()->boolean(45) && $users->count() > 1) {
                    $replyUser = $users->where('id', '!=', $user->id)->random();
                    $replyBody = fake()->randomElement($sampleReplies);
                    $replyTime = (clone $createdAt)->addHours(rand(1, 24));

                    Comment::create([
                        'ranking_post_id' => $post->id,
                        'user_id' => $replyUser->id,
                        'created_by' => $replyUser->id,
                        'updated_by' => $replyUser->id,
                        'parent_id' => $comment->id,
                        'body' => $replyBody,
                        'status' => 'visible',
                        'created_at' => $replyTime,
                        'updated_at' => $replyTime,
                    ]);
                }
            }
        }
    }
}
