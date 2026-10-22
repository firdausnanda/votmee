<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

#[Signature('votmee:prune-notifications {--days=90 : Only remove read notifications older than this many days}')]
#[Description('Remove notifications that were read a long time ago')]
class PruneNotifications extends Command
{
    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $removed = DatabaseNotification::query()->whereNotNull('read_at')->where('read_at', '<', now('UTC')->subDays($days))->delete();

        $this->info("Removed {$removed} notification(s) read more than {$days} day(s) ago.");

        return self::SUCCESS;
    }
}
