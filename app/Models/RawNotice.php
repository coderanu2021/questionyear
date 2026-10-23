<?php

namespace App\Models;

use Database\Factories\RawNoticeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawNotice extends Model
{
    /** @use HasFactory<RawNoticeFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fetched_at' => 'datetime'];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(JobSource::class, 'source_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(JobNotification::class, 'raw_notice_id');
    }
}
