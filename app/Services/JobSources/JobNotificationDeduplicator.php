<?php

namespace App\Services\JobSources;

use App\Models\JobNotification;

class JobNotificationDeduplicator
{
    public function findDuplicate(?string $advertisementNo, ?string $organization, string $title): ?JobNotification
    {
        $deduplicationHash = JobNotification::deduplicationHash($advertisementNo, $organization, $title);

        return JobNotification::query()->where('deduplication_hash', $deduplicationHash)->first();
    }
}
