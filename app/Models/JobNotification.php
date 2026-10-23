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

    public function rawNotice(): BelongsTo
    {
        return $this->belongsTo(RawNotice::class, 'raw_notice_id');
    }
}
