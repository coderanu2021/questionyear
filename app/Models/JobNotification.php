<?php

namespace App\Models;

use Database\Factories\JobNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobNotification extends Model
{
    /** @use HasFactory<JobNotificationFactory> */
    use HasFactory;

    protected $table = 'notifications';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'last_date' => 'date',
            'source_checked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (JobNotification $notification): void {
            if ($notification->isDirty(['advertisement_no', 'organization', 'title'])) {
                $notification->deduplication_hash = self::deduplicationHash(
                    $notification->advertisement_no,
                    $notification->organization,
                    $notification->title,
                );
            }
        });
    }

    public static function deduplicationHash(?string $advertisementNo, ?string $organization, string $title): string
    {
        $normalizedValues = array_map(static function (?string $value): string {
            $value = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value ?? '') ?? ''));

            return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? '');
        }, [$advertisementNo, $organization, $title]);

        return hash('sha256', json_encode($normalizedValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    public function rawNotice(): BelongsTo
    {
        return $this->belongsTo(RawNotice::class, 'raw_notice_id');
    }
}
