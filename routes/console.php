<?php

use App\Models\User;
use App\Services\AchievementService;
use App\Services\OrphanImagePruner;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('votmee:grant-role {email} {role}', function () {
    $role = (string) $this->argument('role');

    if (! in_array($role, ['user', 'moderator', 'admin'], true)) {
        $this->error('Role must be user, moderator, or admin.');

        return 1;
    }

    $user = User::query()->where('email', $this->argument('email'))->firstOrFail();

    DB::transaction(function () use ($user, $role): void {
        $user->forceFill(['role' => $role])->save();

        if ($role === 'admin') {
            $user->syncRoles(['administrator']);
        } elseif ($role === 'user') {
            $user->syncRoles(['user']);
        }

        DB::table('audit_logs')->insert([
            'actor_id' => null,
            'action' => 'user.role.grant',
            'target_type' => 'user',
            'target_id' => (string) $user->id,
            'details' => json_encode(['role' => $role, 'source' => 'console'], JSON_THROW_ON_ERROR),
            'created_at' => now('UTC'),
        ]);
    });

    $this->info("Role {$role} assigned to {$user->email}.");

    return 0;
})->purpose('Assign a Votmee role to an existing account');

Artisan::command('votmee:achievements-backfill', function (AchievementService $achievements) {
    $unlocked = 0;

    User::query()->orderBy('id')->each(function (User $user) use ($achievements, &$unlocked): void {
        foreach (array_keys(config('gamification.achievements')) as $category) {
            $unlocked += count($achievements->evaluate($user, $category, awardPoints: false));
        }
    });

    $this->info("Unlocked {$unlocked} achievements without awarding points.");
})->purpose('Unlock badges members already earned before achievements existed, without paying their points');

Artisan::command('votmee:crown-champions {--month= : The finished Jakarta month to crown, as YYYY-MM; defaults to last month}', function (AchievementService $achievements) {
    $option = $this->option('month');

    if ($option !== null && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $option) !== 1) {
        $this->error('Month must look like 2026-09.');

        return 1;
    }

    $month = $option === null
        ? $achievements->currentMonth()->subMonth()
        : CarbonImmutable::createFromFormat('!Y-m', (string) $option, 'Asia/Jakarta');

    if (! $month->lessThan($achievements->currentMonth())) {
        $this->error('Only a finished month can be crowned.');

        return 1;
    }

    $crowned = $achievements->crownChampions($month);
    $this->info("Awarded {$crowned} champion title(s) for {$month->format('Y-m')}.");

    return 0;
})->purpose('Give the leaders of last month\'s hall of fame their permanent champion titles');

Artisan::command('votmee:prune-images {--hours=24 : Only remove uploads older than this many hours} {--dry-run : Report what would be removed without deleting}', function (OrphanImagePruner $pruner) {
    $hours = max(1, (int) $this->option('hours'));
    $dryRun = (bool) $this->option('dry-run');
    $result = $pruner->prune(now()->subHours($hours), $dryRun);

    $this->info(($dryRun ? 'Would remove' : 'Removed')." {$result['unused_content_images']} unused editor image(s) and {$result['orphan_files']} orphan file(s) older than {$hours} hour(s).");

    return 0;
})->purpose('Remove uploaded images that are no longer referenced');

Schedule::command('votmee:prune-images')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('votmee:prune-notifications')->dailyAt('03:30')->withoutOverlapping();
Schedule::command('votmee:streak-reminders')->dailyAt('19:00')->timezone('Asia/Jakarta')->withoutOverlapping();
Schedule::command('votmee:crown-champions')->monthlyOn(1, '01:00')->timezone('Asia/Jakarta')->withoutOverlapping();
Schedule::command('votmee:auto-rankings')->dailyAt('10:00')->timezone('Asia/Jakarta')->withoutOverlapping();
