<?php

namespace App\Console\Commands;

use App\Models\JobNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:close-expired-job-notifications')]
#[Description('Close published government job notices after their confirmed last date')]
class CloseExpiredJobNotifications extends Command
{
    public function handle(): int
    {
        $closedCount = JobNotification::query()
            ->where('status', 'published')
            ->whereNotNull('last_date')
            ->whereDate('last_date', '<', today())
            ->update(['status' => 'closed']);

        $this->info("Closed {$closedCount} expired government job notices.");

        return self::SUCCESS;
    }
}
