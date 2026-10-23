<?php

namespace App\Console\Commands;

use App\Services\JobSources\GovernmentJobFetcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('jobs:fetch')]
#[Description('Fetch new notices from active official government job sources')]
class FetchGovernmentJobs extends Command
{
    public function handle(GovernmentJobFetcher $fetcher): int
    {
        $summary = $fetcher->fetchActiveSources();
        $this->info("Checked {$summary['sources']} sources; saved {$summary['fetched']} new raw notices; skipped {$summary['skipped']} sources; failed {$summary['failed']} sources.");

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
