<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $admin = $users->firstWhere('role', 'admin') ?? $users->first();

        if (! $admin) {
            return;
        }

        $auditTemplates = [
            [
                'action' => 'user.login',
                'target_type' => 'user',
                'details' => fn ($u) => ['ip_address' => fake()->ipv4(), 'user_agent' => fake()->userAgent(), 'status' => 'success'],
            ],
            [
                'action' => 'ranking_post.create',
                'target_type' => 'ranking_post',
                'details' => fn ($u) => ['locale' => 'id', 'region' => 'ID', 'candidates_count' => rand(3, 5)],
            ],
            [
                'action' => 'support.vote',
                'target_type' => 'support_event',
                'details' => fn ($u) => ['points' => fake()->randomElement([1, 2, 5, 10]), 'channel' => 'web'],
            ],
            [
                'action' => 'comment.hide',
                'target_type' => 'comment',
                'details' => fn ($u) => ['moderator_id' => $admin->id, 'reason' => 'Pelanggaran pedoman etika komunitas'],
            ],
            [
                'action' => 'report.resolve',
                'target_type' => 'report',
                'details' => fn ($u) => ['moderator_id' => $admin->id, 'status' => 'resolved', 'notes' => 'Tindakan pencegahan spam diterapkan'],
            ],
            [
                'action' => 'object.review',
                'target_type' => 'object',
                'details' => fn ($u) => ['status' => 'verified', 'verified_by' => $admin->id],
            ],
            [
                'action' => 'support.reverse',
                'target_type' => 'support_event',
                'details' => fn ($u) => ['refund_points' => 5, 'reason' => 'Pembatalan transaksi duplikat'],
            ],
            [
                'action' => 'system.daily_bonus.cron',
                'target_type' => 'system',
                'details' => fn ($u) => ['processed_claims' => rand(20, 80), 'timezone' => 'Asia/Jakarta'],
            ],
        ];

        // Seed 30 realistic audit logs
        for ($i = 0; $i < 30; $i++) {
            $template = $auditTemplates[$i % count($auditTemplates)];
            $actor = ($template['target_type'] === 'system') ? null : $users->random();
            $targetId = ($template['target_type'] === 'user' && $actor)
                ? (string) $actor->id
                : (string) rand(1, 100);

            $createdAt = now('UTC')->subDays(rand(0, 14))->subHours(rand(1, 23))->subMinutes(rand(1, 59));

            DB::table('audit_logs')->insert([
                'actor_id' => $actor?->id,
                'action' => $template['action'],
                'target_type' => $template['target_type'],
                'target_id' => $targetId,
                'details' => json_encode(($template['details'])($actor), JSON_THROW_ON_ERROR),
                'created_at' => $createdAt,
            ]);
        }
    }
}
