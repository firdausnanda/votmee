<?php

namespace App\Console\Commands;

use App\Services\AutoRankingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('votmee:auto-rankings {--force : Generate even when members published rankings recently} {--dry-run : Report which categories would be generated without generating}')]
#[Description('Publish rankings from the official account when members have been quiet')]
class AutoRankings extends Command
{
    public function handle(AutoRankingService $autoRankings): int
    {
        $result = $autoRankings->run((bool) $this->option('force'), (bool) $this->option('dry-run'));

        $this->info(match ($result['status']) {
            'disabled' => 'Automatic rankings are disabled.',
            'active' => 'Members published rankings recently; nothing generated.',
            'daily_limit' => 'Today\'s automatic ranking limit is already reached.',
            'queue_full' => 'Too many generated rankings await review; nothing generated.',
            'dry_run' => 'Would try: '.(implode(', ', $result['categories']) ?: 'no eligible category').'.',
            default => 'Generated '.count($result['posts']).' ranking(s)'.($result['categories'] === [] ? '' : ': '.implode(', ', $result['categories'])).'.',
        });

        return self::SUCCESS;
    }
}
