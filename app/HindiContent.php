<?php

namespace App;

use DOMDocument;
use DOMXPath;

class HindiContent
{
    /** @return array<string, string> */
    public static function dictionary(): array
    {
        $ui = json_decode(file_get_contents(resource_path('lang/hi.json')), true, flags: JSON_THROW_ON_ERROR);
        $path = resource_path('lang/hi-content.json');
        $content = is_file($path) ? json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) : [];

        return array_merge($content, $ui);
    }

    public static function translateHtml(string $html): string
    {
        $dictionary = self::dictionary();
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $document->encoding = 'UTF-8';
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//text()[not(ancestor::script) and not(ancestor::style) and not(ancestor::textarea)]') as $node) {
            $text = trim($node->nodeValue);
            if (isset($dictionary[$text])) {
                $node->nodeValue = str_replace($text, $dictionary[$text], $node->nodeValue);
            }
        }
        foreach ($xpath->query('//*[@placeholder or @aria-label or @alt or @title]') as $element) {
            foreach (['placeholder', 'aria-label', 'alt', 'title'] as $attribute) {
                $text = $element->getAttribute($attribute);
                if (isset($dictionary[$text])) {
                    $element->setAttribute($attribute, $dictionary[$text]);
                }
            }
        }
        foreach ($xpath->query('//meta[@name="description" or @name="keywords" or @property="og:title" or @property="og:description"]') as $element) {
            $text = $element->getAttribute('content');
            if (isset($dictionary[$text])) {
                $element->setAttribute('content', $dictionary[$text]);
            }
        }

        return ($document->doctype ? $document->saveHTML($document->doctype) : '').$document->saveHTML($document->documentElement);
    }
}
