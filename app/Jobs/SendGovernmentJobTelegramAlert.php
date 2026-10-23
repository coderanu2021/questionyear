<?php

namespace App\Jobs;

use App\Models\JobNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendGovernmentJobTelegramAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [60, 300, 900, 1800];

    public function __construct(public int $notificationId) {}

    public function handle(): void
    {
        $token = config('services.job_alerts.telegram_bot_token');
        $chatId = config('services.job_alerts.telegram_chat_id');
        $notification = JobNotification::query()->where('status', 'published')->whereNotNull('official_url')->find($this->notificationId);

        if (! $token || ! $chatId || ! $notification) {
            return;
        }

        $message = implode("\n", [
            'Government job notice: '.$notification->title,
            'Organization: '.($notification->organization ?: 'Check official notice'),
            'Last date: '.($notification->last_date?->format('d M Y') ?? 'Check official notice'),
            'Official source: '.$notification->official_url,
            'Source checked on: '.($notification->source_checked_at?->timezone('Asia/Kolkata')->format('d M Y') ?? 'Check official notice'),
        ]);

        Http::timeout(10)->post('https://api.telegram.org/bot'.$token.'/sendMessage', ['chat_id' => $chatId, 'text' => $message])->throw();
    }
}
