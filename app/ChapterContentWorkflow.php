<?php

namespace App;

use App\Jobs\GenerateChapterSection;
use App\Models\Chapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChapterContentWorkflow
{
    public function __construct(public ChapterContentQuality $quality) {}

    /** @return array<string, mixed> */
    public function snapshot(Chapter $chapter): array
    {
        return $chapter->only(['subject_id', 'title', 'category', 'status', 'description', 'content', 'notes', 'study_guide', 'lessons', 'meta_title', 'meta_description', 'meta_keywords', 'content_evidence', 'content_verified_at', 'content_review_due_at']);
    }

    /** @param array<string, mixed> $value */
    public static function hash(array $value): string
    {
        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed> */
    public function revision(int $id): array
    {
        $row = DB::table('chapter_content_revisions')->find($id);
        abort_unless($row, 404);
        $result = (array) $row;
        foreach (['brief', 'payload', 'checks', 'original'] as $key) {
            $result[$key] = json_decode($result[$key] ?? '[]', true, flags: JSON_THROW_ON_ERROR);
        }

        return $result;
    }

    /** @param list<array<string, mixed>> $items */
    public function validateItems(array $items): void
    {
        Validator::make(['items' => $items], [
            'items' => 'required|array|list|min:1|max:5', 'items.*.chapter_id' => 'required|integer|distinct|exists:chapters,id',
            'items.*.size' => 'required|in:small,medium,large', 'items.*.syllabus' => 'required|array|list|min:3|max:20',
            'items.*.review_after_days' => 'sometimes|integer|between:1,365',
            'items.*.syllabus.*' => 'required|string|max:200',
            'items.*.sources' => 'required|array|list|min:1|max:20', 'items.*.sources.*.id' => 'required|string|max:50',
            'items.*.sources.*.title' => 'required|string|max:255', 'items.*.sources.*.url' => 'required|url:https|max:2000',
            'items.*.sources.*.locator' => 'required|string|max:500', 'items.*.sources.*.excerpt' => 'required|string|min:100|max:30000',
        ])->validate();
        foreach ($items as $item) {
            if (count($item['syllabus']) !== count(array_unique(array_map(fn (string $topic): string => mb_strtolower(trim($topic)), $item['syllabus']))) || count($item['sources']) !== count(array_unique(array_column($item['sources'], 'id')))) {
                throw ValidationException::withMessages(['brief' => 'Syllabus headings and source IDs must be unique within each chapter.']);
            }
        }
    }

    /** @param list<array<string, mixed>> $items
     * @return list<int>
     */
    public function start(array $items, int $userId, bool $generate = true): array
    {
        $this->validateItems($items);
        if ($generate && ! config('services.gemini.key')) {
            throw ValidationException::withMessages(['generation' => 'Configure GEMINI_API_KEY before starting generation, or import an editorial draft.']);
        }
        $batch = (string) Str::uuid();

        return DB::transaction(function () use ($items, $userId, $generate, $batch): array {
            $ids = [];
            foreach (collect($items)->sortBy('chapter_id') as $item) {
                $chapter = Chapter::lockForUpdate()->findOrFail($item['chapter_id']);
                if (DB::table('chapter_content_revisions')->where('chapter_id', $chapter->id)->whereNotIn('status', ['published', 'archived'])->exists()) {
                    throw ValidationException::withMessages(['batch' => 'Chapter #'.$chapter->id.' already has a working revision. Edit or resume it.']);
                }
                $brief = ['size' => $item['size'], 'syllabus' => $item['syllabus'], 'review_after_days' => $item['review_after_days'] ?? ($chapter->subject->slug === 'current-affairs' ? 1 : 180), 'sources' => array_map(fn (array $source): array => array_intersect_key($source, array_flip(['id', 'title', 'url', 'locator', 'excerpt'])), $item['sources'])];
                $original = $this->snapshot($chapter);
                $id = DB::table('chapter_content_revisions')->insertGetId([
                    'chapter_id' => $chapter->id, 'batch_id' => $batch, 'brief' => json_encode($brief, JSON_THROW_ON_ERROR),
                    'payload' => '{}', 'original' => json_encode($original, JSON_THROW_ON_ERROR), 'original_hash' => self::hash($original),
                    'created_by' => $userId, 'status' => 'draft', 'generation_state' => $generate ? 'queued' : 'complete',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $ids[] = $id;
                if ($generate) {
                    GenerateChapterSection::dispatch($id, 0)->afterCommit();
                }
                Log::info('Chapter content revision created', ['revision_id' => $id, 'chapter_id' => $chapter->id, 'batch_id' => $batch]);
            }

            return $ids;
        });
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function sanitize(array $payload): array
    {
        foreach ($payload['sections'] ?? [] as $index => $section) {
            $payload['sections'][$index]['html'] = PostContent::render(strip_tags($section['html'], '<p><br><h3><strong><em><b><i><ul><ol><li><blockquote><table><thead><tbody><tr><th><td>'));
        }
        unset($payload['_completed']);

        return $payload;
    }

    /** @param array<string, mixed> $payload
     * @param  array<string, mixed>|null  $brief
     */
    public function save(int $id, int $version, array $payload, ?array $brief = null): void
    {
        $this->quality->validate($payload);
        $payload = $this->sanitize($payload);
        DB::transaction(function () use ($id, $version, $payload, $brief): void {
            DB::table('chapter_content_revisions')->where('id', $id)->lockForUpdate()->first();
            $revision = $this->revision($id);
            abort_unless($revision['version'] === $version && in_array($revision['status'], ['draft', 'review', 'verified'], true) && in_array($revision['generation_state'], ['complete', 'failed'], true), 409, 'Revision changed or is still generating. Reload before editing.');
            $brief ??= $revision['brief'];
            $this->validateItems([[...$brief, 'chapter_id' => $revision['chapter_id']]]);
            $brief = array_intersect_key($brief, array_flip(['size', 'syllabus', 'sources', 'review_after_days']));
            $brief['sources'] = array_map(fn (array $source): array => array_intersect_key($source, array_flip(['id', 'title', 'url', 'locator', 'excerpt'])), $brief['sources']);
            $checks = $this->quality->check(Chapter::findOrFail($revision['chapter_id']), $payload, $brief);
            DB::table('chapter_content_revisions')->where('id', $id)->update([
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'checks' => json_encode($checks, JSON_THROW_ON_ERROR),
                'brief' => json_encode($brief, JSON_THROW_ON_ERROR),
                'status' => 'review', 'generation_state' => 'complete', 'version' => $version + 1,
                'verified_hash' => null, 'verified_at' => null, 'verified_by' => null, 'review_note' => null, 'last_error' => null, 'updated_at' => now(),
            ]);
        });
    }

    public function verify(int $id, int $version, int $reviewerId, string $note): void
    {
        DB::transaction(function () use ($id, $version, $reviewerId, $note): void {
            DB::table('chapter_content_revisions')->where('id', $id)->lockForUpdate()->first();
            $revision = $this->revision($id);
            abort_unless($revision['version'] === $version && $revision['status'] === 'review' && $revision['generation_state'] === 'complete', 409);
            $this->quality->validate($revision['payload']);
            $checks = $this->quality->check(Chapter::findOrFail($revision['chapter_id']), $revision['payload'], $revision['brief']);
            if ($checks['errors']) {
                throw ValidationException::withMessages(['quality' => $checks['errors']]);
            }
            DB::table('chapter_content_revisions')->where('id', $id)->update([
                'status' => 'verified', 'verified_by' => $reviewerId, 'verified_at' => now(),
                'verified_hash' => self::hash(['payload' => $revision['payload'], 'brief' => $revision['brief']]), 'review_note' => $note,
                'checks' => json_encode($checks, JSON_THROW_ON_ERROR), 'version' => $version + 1, 'updated_at' => now(),
            ]);
            Log::info('Chapter content verified by editor', ['revision_id' => $id, 'reviewer_id' => $reviewerId]);
        });
    }

    public function publish(int $id, int $version, bool $approveReplacement): void
    {
        DB::transaction(function () use ($id, $version, $approveReplacement): void {
            $chapterId = $this->revision($id)['chapter_id'];
            $chapter = Chapter::lockForUpdate()->findOrFail($chapterId);
            DB::table('chapter_content_revisions')->where('id', $id)->lockForUpdate()->first();
            $revision = $this->revision($id);
            abort_unless($revision['version'] === $version && $revision['status'] === 'verified' && $revision['verified_at'] && hash_equals($revision['verified_hash'] ?? '', self::hash(['payload' => $revision['payload'], 'brief' => $revision['brief']])), 409, 'Verify the current revision before publishing.');
            abort_unless(hash_equals($revision['original_hash'], self::hash($this->snapshot($chapter))), 409, 'The live chapter has changed since this revision started. Reconcile it before publishing.');
            if ($chapter->status === 'published' && ! $approveReplacement) {
                throw ValidationException::withMessages(['replacement' => 'Explicitly approve replacing this published chapter. Its original snapshot is retained.']);
            }
            $this->quality->validate($revision['payload']);
            $checks = $this->quality->check($chapter, $revision['payload'], $revision['brief']);
            if ($checks['errors']) {
                throw ValidationException::withMessages(['quality' => $checks['errors']]);
            }
            $payload = $revision['payload'];
            $chapter->fill([
                'content' => $this->render($payload, $revision['brief']['sources']), 'notes' => null, 'study_guide' => null,
                'description' => $payload['meta_description'], 'meta_title' => $payload['seo_title'], 'meta_description' => $payload['meta_description'],
                'lessons' => count($payload['sections']), 'status' => 'published',
                'content_evidence' => ['revision_id' => $id, 'sources' => $revision['brief']['sources'], 'faqs' => $payload['faqs'], 'suggested_slug' => $payload['slug'], 'reviewer_id' => $revision['verified_by']],
                'content_verified_at' => $revision['verified_at'], 'content_review_due_at' => now()->addDays($revision['brief']['review_after_days'] ?? 180),
            ])->save();
            $quiz = $chapter->quizzes()->create(['title' => $chapter->title.' — Reviewed practice '.$id, 'questions' => $payload['questions'], 'duration' => 25, 'passing_score' => 40, 'status' => 'published']);
            $chapter->update(['content_evidence' => [...$chapter->content_evidence, 'quiz_id' => $quiz->id]]);
            DB::table('chapter_content_revisions')->where('id', $id)->update(['status' => 'published', 'published_at' => now(), 'version' => $version + 1, 'updated_at' => now()]);
            Log::info('Verified chapter content published', ['revision_id' => $id, 'chapter_id' => $chapter->id, 'reviewer_id' => $revision['verified_by']]);
        });
    }

    /** @param array<string, mixed> $payload
     * @param  list<array<string, string>>  $sources
     */
    public function render(array $payload, array $sources): string
    {
        $html = '';
        foreach ($payload['sections'] ?? [] as $section) {
            $html .= '<h2>'.e($section['heading']).'</h2>'.PostContent::render($section['html']);
        }
        foreach (['exam_facts' => 'Important exam facts', 'misconceptions' => 'Common mistakes and misconceptions', 'summary' => 'Revision summary'] as $key => $heading) {
            $html .= '<h2>'.$heading.'</h2><ul>';
            foreach ($payload[$key] ?? [] as $item) {
                $html .= '<li>'.e($item).'</li>';
            }
            $html .= '</ul>';
        }
        $html .= '<h2>Practice MCQs with explanations</h2>';
        foreach ($payload['questions'] ?? [] as $index => $question) {
            $html .= '<h3>'.($index + 1).'. '.e($question['q']).'</h3><ol>';
            foreach ($question['o'] as $option) {
                $html .= '<li>'.e($option).'</li>';
            }
            $html .= '</ol><p><strong>Answer: '.e($question['o'][$question['c']]).'.</strong> '.e($question['explanation']).'</p>';
        }
        $html .= '<h2>Frequently asked questions</h2>';
        foreach ($payload['faqs'] ?? [] as $faq) {
            $html .= '<h3>'.e($faq['q']).'</h3><p>'.e($faq['a']).'</p>';
        }
        $html .= '<h2>Sources and references</h2><ul>';
        foreach ($sources as $source) {
            $html .= '<li><a href="'.e($source['url']).'" rel="noopener noreferrer">'.e($source['title']).'</a> — '.e($source['locator']).'</li>';
        }

        return $html.'</ul>';
    }

    /** @return array{words: int, issues: list<string>} */
    public function audit(Chapter $chapter): array
    {
        $text = ($chapter->content ?? '').' '.implode(' ', array_map(fn (array $section): string => implode(' ', $section['p'] ?? []), array_merge($chapter->notes['s'] ?? [], $chapter->study_guide['s'] ?? [])));
        $words = ChapterContentQuality::words($text);
        $issues = [];
        if ($words === 0) {
            $issues[] = 'Missing explanation';
        } elseif ($words < 1500) {
            $issues[] = 'Depth review: fewer than 1,500 explanatory words';
        }
        if (! $chapter->content_verified_at) {
            $issues[] = 'No recorded factual verification';
        }
        if (! ($chapter->content_evidence['sources'] ?? [])) {
            $issues[] = 'No recorded source dossier';
        }
        if ($chapter->content_review_due_at?->isPast()) {
            $issues[] = 'Review overdue; check time-sensitive facts';
        }
        if ($chapter->quizzes->sum(fn ($quiz): int => count($quiz->questions ?? [])) < 20) {
            $issues[] = 'Fewer than 20 practice questions';
        }
        if (count($chapter->content_evidence['faqs'] ?? []) < 5) {
            $issues[] = 'Fewer than five recorded FAQs';
        }

        return ['words' => $words, 'issues' => $issues];
    }
}
