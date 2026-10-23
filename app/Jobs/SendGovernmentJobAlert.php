<?php

namespace App\Jobs;

use App\Models\JobNotification;
use App\Services\JobAlerts\WebPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendGovernmentJobAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [60, 300, 900, 1800];

    public function __construct(public int $notificationId) {}

    public function handle(WebPushService $webPushService): void
    {
        $notification = JobNotification::query()->where('status', 'published')->whereNotNull('official_url')->find($this->notificationId);
        if ($notification) {
            $webPushService->send($notification);
        }
    }
}
