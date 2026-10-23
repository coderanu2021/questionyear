<?php

namespace App\Services\JobSources;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class OfficialSourceHttpClient
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly HostRequestPacer $pacer,
        private readonly OfficialSourceUrlPolicy $urlPolicy,
    ) {}

    public function get(string $url, string $sourceUrl): ResponseInterface
    {
        if (! $this->urlPolicy->allows($url, $sourceUrl)) {
            throw new \InvalidArgumentException('Only official government source URLs can be fetched.');
        }

        $currentUrl = $url;
        for ($redirects = 0; $redirects <= 3; $redirects++) {
            $response = $this->requestWithRetry($currentUrl);
            if (! in_array($response->getStatusCode(), [301, 302, 303, 307, 308], true)) {
                return $response;
            }

            $location = $response->getHeaderLine('Location');
            if ($location === '' || $redirects === 3) {
                return $response;
            }

            $currentUrl = (string) UriResolver::resolve(new Uri($currentUrl), new Uri($location));
            if (! $this->urlPolicy->allows($currentUrl, $sourceUrl)) {
                throw new \RuntimeException('An official source redirected outside the approved government domains.');
            }
        }

        throw new \RuntimeException('The official source exceeded its redirect limit.');
    }

    private function requestWithRetry(string $url): ResponseInterface
    {
        $lastException = null;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->pacer->wait($url);

            try {
                $response = $this->client->request('GET', $url, [
                    'allow_redirects' => false,
                    'connect_timeout' => 10,
                    'headers' => [
                        'Accept' => 'text/html,application/rss+xml,application/atom+xml,application/pdf;q=0.9,*/*;q=0.1',
                        'User-Agent' => 'QuestionYearBot/1.0 (+https://questionyear.in/)',
                    ],
                    'http_errors' => false,
                    'timeout' => 25,
                ]);

                if (! in_array($response->getStatusCode(), [429, 500, 502, 503, 504], true) || $attempt === 2) {
                    return $response;
                }
            } catch (Throwable $exception) {
                $lastException = $exception;
                if ($attempt === 2) {
                    throw new \RuntimeException('Official source request failed after retries.', previous: $exception);
                }
            }

            usleep((500_000 * (2 ** $attempt)) + random_int(0, 150_000));
        }

        throw new \RuntimeException('Official source request failed after retries.', previous: $lastException);
    }
}
