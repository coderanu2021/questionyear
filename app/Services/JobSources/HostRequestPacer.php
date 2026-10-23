<?php

namespace App\Services\JobSources;

use Illuminate\Support\Facades\Cache;

class HostRequestPacer
{
    public function wait(string $url, int $minimumDelayMilliseconds = 1000): void
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $lock = Cache::lock('job-source-rate-lock:'.sha1($host), 10);
        $minimumDelayMilliseconds = max($minimumDelayMilliseconds, (int) Cache::get('job-source-crawl-delay:'.sha1($host), 1000));

        $lock->block(15, function () use ($host, $minimumDelayMilliseconds): void {
            $cacheKey = 'job-source-last-request:'.sha1($host);
            $lastRequestAt = (float) Cache::get($cacheKey, 0);
            $remainingMilliseconds = $minimumDelayMilliseconds - (int) ((microtime(true) - $lastRequestAt) * 1000);

            if ($remainingMilliseconds > 0) {
                usleep($remainingMilliseconds * 1000);
            }

            Cache::put($cacheKey, microtime(true), 60);
        });
    }

    public function setMinimumDelay(string $url, int $minimumDelayMilliseconds): void
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        Cache::put('job-source-crawl-delay:'.sha1($host), max(1000, $minimumDelayMilliseconds), now()->addHours(6));
    }
}
