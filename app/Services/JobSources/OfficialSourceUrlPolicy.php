<?php

namespace App\Services\JobSources;

class OfficialSourceUrlPolicy
{
    public function allows(string $url, string $sourceUrl): bool
    {
        $target = parse_url($url);
        $source = parse_url($sourceUrl);

        if (! is_array($target) || ! is_array($source)
            || ! in_array($target['scheme'] ?? null, ['http', 'https'], true)
            || ! isset($target['host'], $source['host'])
            || (isset($target['port']) && ! in_array($target['port'], [80, 443], true))
            || isset($target['user']) || isset($target['pass'])) {
            return false;
        }

        $host = strtolower(rtrim($target['host'], '.'));
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return false;
        }

        $isGovernmentHost = str_ends_with($host, '.gov.in')
            || $host === 'gov.in'
            || str_ends_with($host, '.nic.in')
            || $host === 'nic.in'
            || str_ends_with($host, '.indianrailways.gov.in')
            || $host === 'indianrailways.gov.in'
            || $host === 'ibps.in'
            || str_ends_with($host, '.ibps.in');

        return $isGovernmentHost;
    }
}
