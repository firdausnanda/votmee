<?php

namespace App\Console\Commands;

use App\Models\Wallet;
use App\Notifications\StreakReminder;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Signature('votmee:streak-reminders {--dry-run : Report how many reminders would be sent without sending them}')]
#[Description('Remind members whose daily streak is alive but not yet claimed today')]
class StreakReminders extends Command
{
    public function handle(GamificationService $gamification, NotificationService $notifications): int
    {
        $today = $gamification->today();
        $yesterday = CarbonImmutable::parse($today)->subDay()->toDateString();
        $startOfToday = CarbonImmutable::parse($today, 'Asia/Jakarta')->utc();
        $dryRun = (bool) $this->option('dry-run');
        $sent = 0;

        Wallet::query()
            ->with('user')
            ->whereDate('last_claimed_on', $yesterday)
            ->where('streak_days', '>=', max(1, (int) config('gamification.streak.reminder_min_days')))
            ->chunkById(200, function (Collection $wallets) use ($notifications, $startOfToday, $dryRun, &$sent): void {
                foreach ($wallets as $wallet) {
                    $user = $wallet->user;

                    if ($user === null || ! $user->canParticipate() || ! $user->wantsNotification(StreakReminder::TYPE)
                        || $user->notifications()->where('type', StreakReminder::class)->where('created_at', '>=', $startOfToday)->exists()) {
                        continue;
                    }

                    if (! $dryRun) {
                        $notifications->streakReminder($user, $wallet->streak_days);
                    }

                    $sent++;
                }
            });

        $this->info(($dryRun ? 'Would send' : 'Sent')." {$sent} streak reminder(s).");

        return self::SUCCESS;
    }
}
