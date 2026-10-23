<?php

namespace App\Console\Commands;

use App\Jobs\SendGovernmentJobDigest as SendGovernmentJobDigestJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-government-job-digest')]
#[Description('Queue the daily government job notification email digest')]
class SendGovernmentJobDigest extends Command
{
    public function handle(): int
    {
        SendGovernmentJobDigestJob::dispatch();
        $this->info('Government job email digest queued.');

        return self::SUCCESS;
    }
}
