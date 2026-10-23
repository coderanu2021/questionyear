<?php

namespace App\Models;

use Database\Factories\JobSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobSource extends Model
{
    /** @use HasFactory<JobSourceFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'trusted' => 'boolean', 'last_run_at' => 'datetime', 'terms_reviewed_at' => 'datetime', 'terms_allows_automated_access' => 'boolean'];
    }

    public function rawNotices(): HasMany
    {
        return $this->hasMany(RawNotice::class, 'source_id');
    }
}
