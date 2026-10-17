<?php

namespace App;

use App\Models\Chapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChapterContentQuality
{
    public const MINIMUM_WORDS = ['small' => 1500, 'medium' => 3000, 'large' => 5000];

    public static function text(string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace('><', '> <', $html)), ENT_QUOTES, 'UTF-8')) ?? '');
    }

    public static function words(string $text): int
    {
        return count(preg_split('/\s+/u', self::text($text), -1, PREG_SPLIT_NO_EMPTY));
    }

    /** @return array<string, true> */
    private function shingles(string $text, int $width = 5): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower(self::text($text)), -1, PREG_SPLIT_NO_EMPTY);
        $result = [];
        for ($index = 0; $index <= count($words) - $width; $index++) {
            $result[hash('sha256', implode(' ', array_slice($words, $index, $width)))] = true;
        }

        return $result;
    }

    /** @param array<string, true> $candidate */
    private function isNearDuplicate(array $candidate, string $other): bool
    {
        $comparison = $this->shingles($other);
        $minimum = min(count($candidate), count($comparison));

        return $minimum >= 100 && count(array_intersect_key($candidate, $comparison)) / $minimum >= 0.8;
    }

    /** @param array<string, mixed> $payload */
    public function validate(array $payload): void
    {
        Validator::make($payload, [
            'seo_title' => 'required|string|max:255', 'meta_description' => 'required|string|max:170',
            'slug' => ['required', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'sections' => 'required|array|list|min:3|max:30', 'sections.*.heading' => 'required|string|max:200',
            'sections.*.html' => 'required|string|max:100000', 'sections.*.source_ids' => 'required|array|list|min:1',
            'sections.*.source_ids.*' => 'required|string|max:50',
            'exam_facts' => 'required|array|list|min:5', 'exam_facts.*' => 'required|string|max:2000',
            'misconceptions' => 'required|array|list|min:3', 'misconceptions.*' => 'required|string|max:2000',
            'summary' => 'required|array|list|min:5', 'summary.*' => 'required|string|max:2000',
            'questions' => 'required|array|list|min:20|max:100', 'questions.*.q' => 'required|string|max:2000',
            'questions.*.o' => 'required|array|list|size:4', 'questions.*.o.*' => 'required|string|max:1000',
            'questions.*.c' => 'required|integer|between:0,3', 'questions.*.explanation' => 'required|string|max:4000',
            'questions.*.source_ids' => 'required|array|list|min:1', 'questions.*.source_ids.*' => 'required|string|max:50',
            'faqs' => 'required|array|list|min:5|max:10', 'faqs.*.q' => 'required|string|max:500', 'faqs.*.a' => 'required|string|max:4000',
            'uncertainties' => 'present|array|list', 'uncertainties.*' => 'required|string|max:2000',
        ])->validate();
    }

    /** @param array<string, mixed> $payload
     * @param  array<string, mixed>  $brief
     * @return array{errors: list<string>, warnings: list<string>, words: int}
     */
    public function check(Chapter $chapter, array $payload, array $brief): array
    {
        $errors = [];
        $warnings = [];
        $body = implode(' ', array_column($payload['sections'] ?? [], 'html'));
        $fingerprints = $this->shingles($body);
        $words = self::words($body);
        if ($words < self::MINIMUM_WORDS[$brief['size']]) {
            $errors[] = 'Explanatory sections are below the minimum depth for this chapter size (MCQs and FAQs are excluded).';
        }
        $headings = array_map(fn (array $section): string => mb_strtolower(trim($section['heading'])), $payload['sections'] ?? []);
        if (count(array_unique($headings)) !== count($headings)) {
            $errors[] = 'Repeated explanatory section headings.';
        }
        foreach ($brief['syllabus'] as $topic) {
            if (! in_array(mb_strtolower(trim($topic)), $headings, true)) {
                $errors[] = 'Missing approved syllabus heading: '.$topic;
            }
        }
        if (! preg_match('/<h3\b/i', $body)) {
            $errors[] = 'Add meaningful H3 subheadings inside the explanatory sections.';
        }
        $sourceIds = array_column($brief['sources'], 'id');
        $passages = $this->shingles($body, 35);
        foreach ($brief['sources'] as $source) {
            if (array_intersect_key($passages, $this->shingles($source['excerpt'], 35))) {
                $errors[] = 'A long passage matches supplied source wording; rewrite it in original language.';
            }
        }
        foreach (array_merge($payload['sections'] ?? [], $payload['questions'] ?? []) as $part) {
            if (array_diff($part['source_ids'] ?? [], $sourceIds) !== []) {
                $errors[] = 'A section or MCQ cites a source outside the supplied source dossier.';
            }
        }
        if ($payload['uncertainties'] ?? []) {
            $errors[] = 'Resolve every uncertainty before verification.';
        }
        foreach (['exam_facts', 'misconceptions', 'summary'] as $field) {
            $items = array_map(fn (string $item): string => mb_strtolower(self::text($item)), $payload[$field] ?? []);
            if (count(array_unique($items)) !== count($items)) {
                $errors[] = 'Repeated items in '.$field.'.';
            }
        }
        $faqQuestions = array_map(fn (array $faq): string => mb_strtolower(trim($faq['q'])), $payload['faqs'] ?? []);
        if (count(array_unique($faqQuestions)) !== count($faqQuestions)) {
            $errors[] = 'Repeated FAQ questions.';
        }
        $questions = array_map(fn (array $q): string => mb_strtolower(self::text($q['q'])), $payload['questions'] ?? []);
        if (count(array_unique($questions)) !== count($questions)) {
            $errors[] = 'Repeated MCQ questions.';
        }
        foreach ($payload['questions'] ?? [] as $question) {
            if (count(array_unique(array_map(fn (string $option): string => mb_strtolower(trim($option)), $question['o']))) !== 4) {
                $errors[] = 'An MCQ has duplicate answer options.';
            }
        }
        $paragraphs = preg_split('/<\/p>/i', $body, -1, PREG_SPLIT_NO_EMPTY);
        $seen = [];
        foreach ($paragraphs as $paragraph) {
            $text = mb_strtolower(self::text($paragraph));
            if (self::words($text) < 20) {
                continue;
            }
            $hash = hash('sha256', $text);
            if (isset($seen[$hash])) {
                $errors[] = 'Repeated explanatory paragraph.';
            }
            $seen[$hash] = $text;
            if (self::words($text) > 100) {
                $warnings[] = 'A paragraph exceeds 100 words; split it for easier reading.';
            }
        }
        $sentences = preg_split('/[.!?]+\s+/', self::text($body), -1, PREG_SPLIT_NO_EMPTY);
        if ($words / max(1, count($sentences)) > 25) {
            $warnings[] = 'Average sentence length exceeds 25 words; review readability.';
        }
        foreach (Chapter::where('id', '!=', $chapter->id)->select(['id', 'content', 'notes', 'study_guide', 'meta_title'])->cursor() as $other) {
            if (mb_strtolower(trim($other->meta_title ?? '')) === mb_strtolower(trim($payload['seo_title'] ?? ''))) {
                $errors[] = 'SEO title duplicates chapter #'.$other->id;
            }
            $otherText = mb_strtolower(self::text(($other->content ?? '').' '.json_encode([$other->notes, $other->study_guide])));
            if ($this->isNearDuplicate($fingerprints, $otherText)) {
                $errors[] = 'Substantial wording overlap with chapter #'.$other->id;
            }
            foreach ($seen as $text) {
                if (str_contains($otherText, $text)) {
                    $errors[] = 'An explanatory paragraph duplicates chapter #'.$other->id;
                    break;
                }
            }
        }
        foreach (DB::table('chapter_content_revisions')->where('chapter_id', '!=', $chapter->id)->whereIn('status', ['review', 'verified', 'published'])->select('payload')->cursor() as $other) {
            $otherPayload = json_decode($other->payload, true);
            if (mb_strtolower(trim($otherPayload['seo_title'] ?? '')) === mb_strtolower(trim($payload['seo_title'] ?? ''))) {
                $errors[] = 'SEO title duplicates another content revision.';
            }
            $otherText = mb_strtolower(self::text(implode(' ', array_column($otherPayload['sections'] ?? [], 'html'))));
            if ($this->isNearDuplicate($fingerprints, $otherText)) {
                $errors[] = 'Substantial wording overlap with another content revision.';
            }
            foreach ($seen as $text) {
                if (str_contains($otherText, $text)) {
                    $errors[] = 'An explanatory paragraph duplicates another content revision.';
                    break;
                }
            }
        }

        return ['errors' => array_values(array_unique($errors)), 'warnings' => array_values(array_unique($warnings)), 'words' => $words];
    }
}
