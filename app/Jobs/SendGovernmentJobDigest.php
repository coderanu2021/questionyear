<?php

namespace App\Jobs;

use App\Mail\GovernmentJobDigestMail;
use App\Models\JobNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendGovernmentJobDigest implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function handle(): void
    {
        $recipients = config('services.job_alerts.email_recipients', []);
        if ($recipients === []) {
            return;
        }

        $notifications = JobNotification::query()
            ->where('status', 'published')->whereNotNull('official_url')
            ->where(function ($query): void {
                $query->where('created_at', '>=', now()->subDay())
                    ->orWhere(function ($closingSoon): void {
                        $closingSoon->whereNotNull('last_date')->whereDate('last_date', '>=', today())->whereDate('last_date', '<=', today()->addDays(7));
                    });
            })->orderByDesc('created_at')->get();

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->send(new GovernmentJobDigestMail($notifications));
            } catch (\Throwable $exception) {
                Log::error('Government job email digest failed.', ['recipient' => $recipient, 'message' => $exception->getMessage()]);
                throw $exception;
            }
        }
    }
}
