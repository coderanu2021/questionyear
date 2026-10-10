<?php

namespace App;

class PostContent
{
    public static function render(string $content): string
    {
        if (! preg_match('/<(p|h[234]|figure|img|ul|ol|table|strong|em|blockquote)\b/i', $content)) {
            return nl2br(e($content));
        }

        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>'.$content.'</body>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $allowedTags = ['p', 'br', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 'ul', 'ol', 'li', 'blockquote', 'figure', 'figcaption', 'img', 'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td'];
        foreach (iterator_to_array($document->getElementsByTagName('*')) as $element) {
            if (in_array($element->tagName, ['html', 'body'], true)) {
                continue;
            }
            if (! in_array($element->tagName, $allowedTags, true)) {
                $element->parentNode?->removeChild($element);

                continue;
            }
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $name = $attribute->name;
                $value = trim($attribute->value);
                $safe = ($element->tagName === 'img' && $name === 'src' && preg_match('~^(https?://|/(?!/))[^\s]+$~i', $value))
                    || ($element->tagName === 'img' && $name === 'alt')
                    || ($element->tagName === 'a' && $name === 'href' && preg_match('~^(https?://|mailto:|/(?!/)|\#)[^\s]*$~i', $value))
                    || (in_array($element->tagName, ['th', 'td'], true) && in_array($name, ['colspan', 'rowspan'], true) && ctype_digit($value));
                if (! $safe) {
                    $element->removeAttribute($name);
                }
            }
        }
        $body = $document->getElementsByTagName('body')->item(0);
        $html = '';
        foreach ($body->childNodes as $node) {
            $html .= $document->saveHTML($node);
        }

        return $html;
    }
}
