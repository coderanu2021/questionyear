<?php

namespace App\Services\JobParsers;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Str;

class RssJobNoticeParser implements JobNoticeParser
{
    public function parse(string $url, string $content): array
    {
        $previousSetting = libxml_use_internal_errors(true);
        $feed = simplexml_load_string($content, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previousSetting);

        if (! $feed) {
            return [];
        }

        $items = $feed->channel->item ?? $feed->entry ?? [];
        $notices = [];
        $baseUri = new Uri($url);

        foreach ($items as $item) {
            $title = trim((string) ($item->title ?? ''));
            $link = trim((string) ($item->link ?? ''));
            if ($link === '' && isset($item->link['href'])) {
                $link = (string) $item->link['href'];
            }

            if ($title === '' || $link === '') {
                continue;
            }

            try {
                $noticeUrl = (string) UriResolver::resolve($baseUri, new Uri($link));
            } catch (\InvalidArgumentException) {
                continue;
            }

            if (! in_array(parse_url($noticeUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
                continue;
            }

            $description = trim(strip_tags((string) ($item->description ?? $item->summary ?? '')));
            $notices[$noticeUrl] = [
                'url' => $noticeUrl,
                'title' => Str::limit($title, 500, ''),
                'raw_text' => Str::limit($description !== '' ? $description : $title, 25000, ''),
            ];
        }

        return array_values(array_slice($notices, 0, 50, true));
    }
}
