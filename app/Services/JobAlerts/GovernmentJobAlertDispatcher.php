<?php

namespace App\Services\JobAlerts;

use App\Jobs\SendGovernmentJobAlert;
use App\Jobs\SendGovernmentJobTelegramAlert;
use App\Models\JobNotification;

class GovernmentJobAlertDispatcher
{
    public function dispatch(JobNotification $notification): void
    {
        if ($notification->status !== 'published' || ! $notification->official_url) {
            return;
        }

        SendGovernmentJobAlert::dispatch($notification->id)->afterCommit();
        SendGovernmentJobTelegramAlert::dispatch($notification->id)->afterCommit();
    }
}
