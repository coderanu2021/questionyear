<?php

namespace App\Services\JobParsers;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class HtmlJobNoticeParser implements JobNoticeParser
{
    private const NOTICE_KEYWORDS = [
        'recruitment', 'notification', 'advertisement', 'vacancy', 'vacancies', 'admit card',
        'e-admit', 'result', 'shortlist', 'application', 'employment notice', 'career',
    ];

    public function parse(string $url, string $content): array
    {
        $notices = [];
        $baseUri = new Uri($url);

        (new Crawler($content, $url))->filterXPath('//a[@href]')->each(function (Crawler $anchor) use ($baseUri, &$notices): void {
            $title = trim(preg_replace('/\s+/u', ' ', $anchor->text('', true)) ?? '');
            $href = trim($anchor->attr('href') ?? '');
            $context = trim(preg_replace('/\s+/u', ' ', $anchor->filterXPath('..')->text('', true)) ?? '');

            if ($title === '' || $href === '' || mb_strlen($title) < 5) {
                return;
            }

            $searchableText = Str::lower($title.' '.$href);
            if (! collect(self::NOTICE_KEYWORDS)->contains(fn (string $keyword): bool => str_contains($searchableText, $keyword))) {
                return;
            }

            try {
                $noticeUrl = (string) UriResolver::resolve($baseUri, new Uri($href));
            } catch (\InvalidArgumentException) {
                return;
            }

            if (! in_array(parse_url($noticeUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
                return;
            }

            $notices[$noticeUrl] = [
                'url' => $noticeUrl,
                'title' => Str::limit($title, 500, ''),
                'raw_text' => Str::limit($context !== '' ? $context : $title, 25000, ''),
            ];
        });

        return array_values(array_slice($notices, 0, 50, true));
    }

    public function extractText(string $content): string
    {
        $crawler = new Crawler($content);
        $body = $crawler->filterXPath('//body');
        $text = $body->count() > 0 ? $body->text(' ', true) : $crawler->text(' ', true);

        return Str::limit(trim(preg_replace('/\s+/u', ' ', $text) ?? ''), 25000, '');
    }

    public function extractTitle(string $content): ?string
    {
        $title = (new Crawler($content))->filterXPath('//title');
        if ($title->count() === 0) {
            return null;
        }

        $text = trim(preg_replace('/\s+/u', ' ', $title->text('', true)) ?? '');

        return $text !== '' ? Str::limit($text, 500, '') : null;
    }
}
