<?php

namespace App\Services\JobSources;

use Illuminate\Support\Facades\Cache;
use Throwable;

class RobotsPolicy
{
    private const USER_AGENT = 'QuestionYearBot';

    public function __construct(
        private readonly OfficialSourceHttpClient $httpClient,
        private readonly OfficialSourceUrlPolicy $urlPolicy,
        private readonly HostRequestPacer $pacer,
    ) {}

    public function allows(string $url, string $sourceUrl): bool
    {
        if (! $this->urlPolicy->allows($url, $sourceUrl)) {
            return false;
        }

        $robotsUrl = $this->robotsUrl($url);
        $policy = Cache::remember('job-source-robots:'.sha1($robotsUrl), now()->addHours(6), function () use ($robotsUrl, $sourceUrl): ?string {
            try {
                $response = $this->httpClient->get($robotsUrl, $sourceUrl);
                $status = $response->getStatusCode();

                if ($status === 404 || $status === 410) {
                    return '';
                }
                if ($status < 200 || $status >= 300) {
                    return null;
                }

                return (string) $response->getBody();
            } catch (Throwable) {
                return null;
            }
        });

        if (! is_string($policy)) {
            return false;
        }

        $this->pacer->setMinimumDelay($url, $policy === '' ? 1000 : $this->crawlDelayMilliseconds($policy));

        return $policy === '' || $this->pathIsAllowed($policy, $url);
    }

    private function pathIsAllowed(string $contents, string $url): bool
    {
        $groups = [];
        $agents = [];
        $rules = [];
        $hasRules = false;

        foreach (preg_split('/\r?\n/', $contents) ?: [] as $line) {
            $line = trim(explode('#', $line, 2)[0]);
            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }

            [$directive, $value] = array_map('trim', explode(':', $line, 2));
            $directive = strtolower($directive);

            if ($directive === 'user-agent') {
                if ($hasRules) {
                    $groups[] = ['agents' => $agents, 'rules' => $rules];
                    $agents = [];
                    $rules = [];
                    $hasRules = false;
                }
                $agents[] = strtolower($value);
            } elseif (in_array($directive, ['allow', 'disallow'], true) && $agents !== []) {
                $rules[] = ['directive' => $directive, 'path' => $value];
                $hasRules = true;
            }
        }
        if ($agents !== []) {
            $groups[] = ['agents' => $agents, 'rules' => $rules];
        }

        $specificGroups = [];
        $wildcardGroups = [];
        foreach ($groups as $group) {
            if (in_array('*', $group['agents'], true)) {
                $wildcardGroups[] = $group;
            } elseif (collect($group['agents'])->contains(fn (string $agent): bool => str_contains(self::USER_AGENT, $agent))) {
                $specificGroups[] = $group;
            }
        }

        $applicableRules = collect($specificGroups !== [] ? $specificGroups : $wildcardGroups)
            ->flatMap(fn (array $group): array => $group['rules'])
            ->filter(fn (array $rule): bool => $rule['path'] !== '')
            ->filter(fn (array $rule): bool => $this->ruleMatches($rule['path'], $url))
            ->sortByDesc(fn (array $rule): int => strlen(str_replace(['*', '$'], '', $rule['path'])))
            ->values();

        if ($applicableRules->isEmpty()) {
            return true;
        }

        $winningRule = $applicableRules->first();
        $longestLength = strlen(str_replace(['*', '$'], '', $winningRule['path']));
        $equallySpecific = $applicableRules->takeWhile(fn (array $rule): bool => strlen(str_replace(['*', '$'], '', $rule['path'])) === $longestLength);

        return $equallySpecific->contains(fn (array $rule): bool => $rule['directive'] === 'allow');
    }

    private function crawlDelayMilliseconds(string $contents): int
    {
        $groups = [];
        $agents = [];
        $delay = null;
        $hasDirectives = false;

        foreach (preg_split('/\r?\n/', $contents) ?: [] as $line) {
            $line = trim(explode('#', $line, 2)[0]);
            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }

            [$directive, $value] = array_map('trim', explode(':', $line, 2));
            $directive = strtolower($directive);
            if ($directive === 'user-agent') {
                if ($hasDirectives) {
                    $groups[] = ['agents' => $agents, 'delay' => $delay];
                    $agents = [];
                    $delay = null;
                    $hasDirectives = false;
                }
                $agents[] = strtolower($value);
            } elseif ($directive === 'crawl-delay' && $agents !== [] && is_numeric($value)) {
                $delay = max($delay ?? 0, (float) $value);
                $hasDirectives = true;
            } elseif (in_array($directive, ['allow', 'disallow'], true) && $agents !== []) {
                $hasDirectives = true;
            }
        }
        if ($agents !== []) {
            $groups[] = ['agents' => $agents, 'delay' => $delay];
        }

        $specificGroups = [];
        $wildcardGroups = [];
        foreach ($groups as $group) {
            if (in_array('*', $group['agents'], true)) {
                $wildcardGroups[] = $group;
            } elseif (collect($group['agents'])->contains(fn (string $agent): bool => str_contains(self::USER_AGENT, $agent))) {
                $specificGroups[] = $group;
            }
        }

        $delays = collect($specificGroups !== [] ? $specificGroups : $wildcardGroups)
            ->pluck('delay')
            ->filter(fn (mixed $value): bool => is_numeric($value));

        return max(1000, (int) ceil(($delays->max() ?? 1) * 1000));
    }

    private function ruleMatches(string $rule, string $url): bool
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '/');
        $query = parse_url($url, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            $path .= '?'.$query;
        }

        $pattern = preg_quote($rule, '/');
        $pattern = str_replace('\\*', '.*', $pattern);
        if (str_ends_with($rule, '$')) {
            $pattern = substr($pattern, 0, -2).'$';
        }

        return preg_match('/^'.$pattern.'/i', $path) === 1;
    }

    private function robotsUrl(string $url): string
    {
        $parts = parse_url($url);

        return ($parts['scheme'] ?? 'https').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '').'/robots.txt';
    }
}
