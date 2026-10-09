<?php

namespace App;

use Illuminate\Validation\ValidationException;

class WordDocumentImporter
{
    private const NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /** @return array{html: string, lines: array<int, string>, has_images: bool} */
    public function extract(string $path): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            $this->fail('Upload a valid .docx Word document.');
        }
        try {
            $entry = $zip->statName('word/document.xml');
            if (! $entry || $entry['size'] > 8 * 1024 * 1024) {
                $this->fail('The document is invalid or its extracted content is too large.');
            }
            $xml = $zip->getFromName('word/document.xml');
        } finally {
            $zip->close();
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new \DOMDocument;
            if (! is_string($xml) || str_contains(strtoupper($xml), '<!DOCTYPE') || ! $document->loadXML($xml, LIBXML_NONET)) {
                $this->fail('The Word document could not be read.');
            }
            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('w', self::NS);
            $body = $xpath->query('/w:document/w:body')->item(0);
            if (! $body) {
                $this->fail('The Word document has no readable content.');
            }
            $html = '';
            foreach ($body->childNodes as $node) {
                $html .= $this->render($node, $xpath);
            }
            $lines = [];
            foreach ($xpath->query('.//w:p', $body) as $paragraph) {
                foreach (explode("\n", $this->text($paragraph, $xpath)) as $line) {
                    if (trim($line) !== '') {
                        $lines[] = trim($line);
                    }
                }
            }
            if (! $lines || strlen($html) > 500000) {
                $this->fail('The document is empty or exceeds the 500 KB extracted content limit.');
            }

            return ['html' => $html, 'lines' => $lines, 'has_images' => $xpath->query('//w:drawing|//w:pict')->length > 0];
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function text(\DOMNode $node, \DOMXPath $xpath): string
    {
        $text = '';
        foreach ($xpath->query('.//w:t|.//w:tab|.//w:br|.//w:cr', $node) as $part) {
            $text .= match ($part->localName) {
                't' => $part->textContent,
                'tab' => ' ',
                default => "\n",
            };
        }

        return trim(str_replace("\u{00A0}", ' ', $text));
    }

    private function render(\DOMNode $node, \DOMXPath $xpath): string
    {
        if ($node->localName === 'p') {
            $text = $this->text($node, $xpath);
            $style = $xpath->evaluate('string(w:pPr/w:pStyle/@w:val)', $node);
            $tag = preg_match('/^Heading([1-6])$/i', $style, $match) ? 'h'.min(4, max(2, (int) $match[1])) : 'p';

            return $text === '' ? '' : '<'.$tag.'>'.nl2br(e($text), false).'</'.$tag.'>';
        }
        $tag = match ($node->localName) {
            'tbl' => 'table', 'tr' => 'tr', 'tc' => 'td', default => null,
        };
        if ($tag === null) {
            return '';
        }
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $this->render($child, $xpath);
        }

        return '<'.$tag.'>'.$html.'</'.$tag.'>';
    }

    /** @param array<int, string> $lines
     * @return array<int, array<string, mixed>>
     */
    public function questions(array $lines, string $mode): array
    {
        $questions = [];
        $current = null;
        foreach ($lines as $line) {
            if (preg_match('/^(?:Q(?:uestion)?\s*)?\d+\s*[.):\-]\s*(.+)$/iu', $line, $match)) {
                if ($current !== null) {
                    $questions[] = $this->finish($current, $mode, count($questions) + 1);
                }
                $current = ['q' => $match[1], 'o' => [], 'answer' => '', 'explanation' => ''];
            } elseif ($current !== null && preg_match('/^(?:Answer|Ans|Correct Answer|उत्तर)\s*[:.\-]\s*(.+)$/iu', $line, $match)) {
                $current['answer'] = trim($match[1]);
            } elseif ($current !== null && preg_match('/^(?:Explanation|व्याख्या)\s*[:.\-]\s*(.*)$/iu', $line, $match)) {
                $current['explanation'] = $match[1];
            } elseif ($current !== null && $mode === 'mcq' && preg_match('/^\(?([A-Da-d])\s*[).:\-]\s*(.+)$/u', $line, $match)) {
                $index = ord(strtoupper($match[1])) - ord('A');
                if (array_key_exists($index, $current['o'])) {
                    $this->fail('Duplicate option in question '.(count($questions) + 1).'.');
                }
                $current['o'][$index] = $match[2];
            } elseif ($current !== null) {
                if ($current['explanation'] !== '') {
                    $current['explanation'] .= "\n".$line;
                } elseif ($current['answer'] !== '') {
                    $current['answer'] .= "\n".$line;
                } elseif ($current['o']) {
                    $current['o'][array_key_last($current['o'])] .= "\n".$line;
                } else {
                    $current['q'] .= "\n".$line;
                }
            }
        }
        if ($current !== null) {
            $questions[] = $this->finish($current, $mode, count($questions) + 1);
        }
        if (! $questions || count($questions) > 200) {
            $this->fail('Use numbered questions (1., 2., …), with 1 to 200 questions per file.');
        }

        return $questions;
    }

    /** @param array<string, mixed> $question
     * @return array<string, mixed>
     */
    private function finish(array $question, string $mode, int $number): array
    {
        if ($question['answer'] === '' || mb_strlen($question['q']) > 5000 || mb_strlen($question['answer']) > 10000 || mb_strlen($question['explanation']) > 10000) {
            $this->fail('Question '.$number.' needs an explicit Answer: line and must fit the content limits.');
        }
        if ($mode === 'qa') {
            return ['q' => $question['q'], 'answer' => $question['answer']];
        }
        ksort($question['o']);
        if (array_keys($question['o']) !== [0, 1, 2, 3] || collect($question['o'])->contains(fn (string $option): bool => mb_strlen($option) > 2000)) {
            $this->fail('Question '.$number.' must have four options labelled A, B, C, D (up to 2000 characters each).');
        }
        $answer = $question['answer'];
        $correct = preg_match('/^\(?([A-Da-d])\)?[.)]?$/', $answer, $match)
            ? ord(strtoupper($match[1])) - ord('A') : array_search($answer, $question['o'], true);
        if ($correct === false) {
            $this->fail('Question '.$number.' has an unclear answer. Use Answer: A, B, C or D.');
        }

        return ['q' => $question['q'], 'o' => array_values($question['o']), 'c' => $correct, 'explanation' => $question['explanation']];
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
