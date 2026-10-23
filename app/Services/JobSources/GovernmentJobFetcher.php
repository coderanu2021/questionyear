<?php

namespace App\Services\JobSources;

use App\Models\JobSource;
use App\Services\JobParsers\HtmlJobNoticeParser;
use App\Services\JobParsers\JobNoticeParser;
use App\Services\JobParsers\PdfJobNoticeParser;
use App\Services\JobParsers\RssJobNoticeParser;
use Illuminate\Support\Facades\Log;
use Throwable;

class GovernmentJobFetcher
{
    public function __construct(
        private readonly OfficialSourceHttpClient $httpClient,
        private readonly OfficialSourceUrlPolicy $urlPolicy,
        private readonly RobotsPolicy $robotsPolicy,
        private readonly HtmlJobNoticeParser $htmlParser,
        private readonly RssJobNoticeParser $rssParser,
        private readonly PdfJobNoticeParser $pdfParser,
    ) {}

    /** @return array{sources: int, fetched: int, skipped: int, failed: int} */
    public function fetchActiveSources(): array
    {
        $summary = ['sources' => 0, 'fetched' => 0, 'skipped' => 0, 'failed' => 0];

        foreach (JobSource::query()->where('active', true)->orderBy('id')->get() as $source) {
            $summary['sources']++;

            if (! $source->terms_allows_automated_access) {
                $summary['skipped']++;

                continue;
            }

            try {
                $result = $this->fetchSource($source);
                $summary['fetched'] += $result['fetched'];
                $summary['skipped'] += $result['skipped'] ? 1 : 0;
            } catch (Throwable $exception) {
                $summary['failed']++;
                Log::warning('Government job source fetch failed.', [
                    'source_id' => $source->id,
                    'source_name' => $source->name,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            } finally {
                $source->forceFill(['last_run_at' => now()])->save();
            }
        }

        return $summary;
    }

    /** @return array{fetched: int, skipped: bool} */
    private function fetchSource(JobSource $source): array
    {
        if (! $this->robotsPolicy->allows($source->url, $source->url)) {
            return ['fetched' => 0, 'skipped' => true];
        }

        $response = $this->httpClient->get($source->url, $source->url);
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new \RuntimeException('Official source returned HTTP '.$response->getStatusCode().'.');
        }

        $content = (string) $response->getBody();
        $this->assertPayloadSize($content, $response->getHeaderLine('Content-Length'));
        $contentType = strtolower($response->getHeaderLine('Content-Type'));
        $parser = $this->parserFor($source->parser, $contentType, $source->url);

        if ($parser instanceof PdfJobNoticeParser) {
            return ['fetched' => $this->saveParsedNotices($source, $parser->parse($source->url, $content)), 'skipped' => false];
        }

        $discoveredNotices = $parser->parse($source->url, $content);
        $fetchedCount = 0;
        $skippedCount = false;

        foreach (array_slice($discoveredNotices, 0, 20) as $notice) {
            if (! $this->urlPolicy->allows($notice['url'], $source->url)) {
                continue;
            }

            if (! $this->robotsPolicy->allows($notice['url'], $source->url)) {
                $fetchedCount += $this->saveParsedNotices($source, [$notice]);
                $skippedCount = true;

                continue;
            }

            try {
                $noticeResponse = $this->httpClient->get($notice['url'], $source->url);
                if ($noticeResponse->getStatusCode() < 200 || $noticeResponse->getStatusCode() >= 300) {
                    $fetchedCount += $this->saveParsedNotices($source, [$notice]);

                    continue;
                }

                $noticeContent = (string) $noticeResponse->getBody();
                $this->assertPayloadSize($noticeContent, $noticeResponse->getHeaderLine('Content-Length'));
                $noticeType = strtolower($noticeResponse->getHeaderLine('Content-Type'));
                $noticeParser = $this->parserFor($source->parser, $noticeType, $notice['url']);

                if ($noticeParser instanceof PdfJobNoticeParser) {
                    $parsedNotice = $noticeParser->parse($notice['url'], $noticeContent)[0] ?? $notice;
                    $parsedNotice['title'] = $notice['title'];
                } elseif ($noticeParser instanceof HtmlJobNoticeParser) {
                    $noticeText = $noticeParser->extractText($noticeContent);
                    $parsedNotice = [
                        'url' => $notice['url'],
                        'title' => $noticeParser->extractTitle($noticeContent) ?? $notice['title'],
                        'raw_text' => $noticeText !== '' ? $noticeText : $notice['raw_text'],
                    ];
                } else {
                    $parsedNotice = $noticeParser->parse($notice['url'], $noticeContent)[0] ?? $notice;
                }

                $fetchedCount += $this->saveParsedNotices($source, [$parsedNotice]);
            } catch (Throwable $exception) {
                Log::notice('Official job notice could not be fetched; listing text was retained.', [
                    'source_id' => $source->id,
                    'url' => $notice['url'],
                    'exception' => $exception::class,
                ]);
                $fetchedCount += $this->saveParsedNotices($source, [$notice]);
            }
        }

        return ['fetched' => $fetchedCount, 'skipped' => $skippedCount];
    }

    /** @param array<int, array{url: string, title: string, raw_text: string}> $notices */
    private function saveParsedNotices(JobSource $source, array $notices): int
    {
        $createdCount = 0;

        foreach ($notices as $notice) {
            $title = trim($notice['title']);
            $rawText = trim($notice['raw_text']);
            if ($title === '' || $rawText === '') {
                continue;
            }

            $hash = hash('sha256', mb_strtolower($notice['url'].'|'.$title.'|'.$rawText));
            $rawNotice = $source->rawNotices()->firstOrCreate(['hash' => $hash], [
                'url' => $notice['url'],
                'title' => $title,
                'raw_text' => $rawText,
                'fetched_at' => now(),
            ]);

            if ($rawNotice->wasRecentlyCreated) {
                $createdCount++;
            }
        }

        return $createdCount;
    }

    private function parserFor(string $configuredParser, string $contentType, string $url): JobNoticeParser
    {
        if (str_contains($contentType, 'application/pdf') || str_ends_with(strtolower(parse_url($url, PHP_URL_PATH) ?? ''), '.pdf') || $configuredParser === 'pdf') {
            return $this->pdfParser;
        }
        if (str_contains($contentType, 'rss') || str_contains($contentType, 'atom') || $configuredParser === 'rss') {
            return $this->rssParser;
        }
        if (in_array($configuredParser, ['html', 'generic_html'], true) || str_contains($contentType, 'html') || $contentType === '') {
            return $this->htmlParser;
        }

        throw new \RuntimeException('Unsupported source parser: '.$configuredParser.'.');
    }

    private function assertPayloadSize(string $content, string $contentLength): void
    {
        $size = ctype_digit($contentLength) ? (int) $contentLength : strlen($content);
        if ($size > 20 * 1024 * 1024) {
            throw new \RuntimeException('Official source response exceeded the 20 MB safety limit.');
        }
    }
}
