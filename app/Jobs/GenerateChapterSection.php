<?php

namespace App\Jobs;

use App\ChapterContentGenerator;
use App\ChapterContentWorkflow;
use App\Models\Chapter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GenerateChapterSection implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 150;

    public bool $failOnTimeout = true;

    public function __construct(public int $revisionId, public int $stage)
    {
        $this->onConnection('chapter-content');
        $this->onQueue('chapter-content');
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120];
    }

    /** @return list<WithoutOverlapping> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('chapter-content-'.$this->revisionId))->releaseAfter(15)->expireAfter(160)];
    }

    public function handle(ChapterContentWorkflow $workflow, ChapterContentGenerator $generator): void
    {
        $revision = $workflow->revision($this->revisionId);
        if ($revision['status'] !== 'draft' || ($revision['payload']['_completed'] ?? 0) !== $this->stage) {
            return;
        }
        DB::table('chapter_content_revisions')->where('id', $this->revisionId)->update(['generation_state' => 'generating', 'updated_at' => now()]);
        try {
            $result = $generator->generate($revision, $this->stage);
        } catch (RuntimeException $exception) {
            if (get_class($exception) === RuntimeException::class) {
                DB::table('chapter_content_revisions')->where('id', $this->revisionId)->where('status', 'draft')->update(['last_error' => $exception->getMessage(), 'updated_at' => now()]);
            }
            throw $exception;
        }
        DB::transaction(function () use ($workflow, $result): void {
            DB::table('chapter_content_revisions')->where('id', $this->revisionId)->lockForUpdate()->first();
            $current = $workflow->revision($this->revisionId);
            if ($current['status'] !== 'draft' || ($current['payload']['_completed'] ?? 0) !== $this->stage) {
                return;
            }
            $count = count($current['brief']['syllabus']);
            $payload = $current['payload'];
            $uncertainties = array_merge($payload['uncertainties'] ?? [], $result['uncertainties']);
            unset($result['uncertainties']);
            if ($this->stage < $count) {
                $result['heading'] = $current['brief']['syllabus'][$this->stage];
                $result['html'] = $workflow->sanitize(['sections' => [$result]])['sections'][0]['html'];
                $payload['sections'][] = $result;
            } elseif ($this->stage === $count) {
                $payload = array_merge($payload, $result);
            } else {
                $payload['questions'] = array_merge($payload['questions'] ?? [], $result['questions']);
            }
            $payload['uncertainties'] = array_values(array_unique($uncertainties));
            $payload['_completed'] = $this->stage + 1;
            $finished = $this->stage === $count + 2;
            $checks = [];
            if ($finished) {
                $workflow->quality->validate($payload);
                $checks = $workflow->quality->check(Chapter::findOrFail($current['chapter_id']), $payload, $current['brief']);
            }
            DB::table('chapter_content_revisions')->where('id', $this->revisionId)->update([
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'checks' => json_encode($checks, JSON_THROW_ON_ERROR),
                'generation_state' => $finished ? 'complete' : 'queued', 'status' => $finished ? 'review' : 'draft',
                'version' => $current['version'] + 1, 'last_error' => null, 'updated_at' => now(),
            ]);
            Log::info('Chapter section completed', ['revision_id' => $this->revisionId, 'stage' => $this->stage, 'total_stages' => $count + 3]);
            if (! $finished) {
                self::dispatch($this->revisionId, $this->stage + 1)->afterCommit();
            }
        });
    }

    public function failed(?Throwable $exception): void
    {
        DB::table('chapter_content_revisions')->where('id', $this->revisionId)->where('status', 'draft')->update([
            'generation_state' => 'failed', 'last_error' => $exception && get_class($exception) === RuntimeException::class ? $exception->getMessage() : 'Section '.($this->stage + 1).' failed after retries. Check provider configuration, source coverage and response format; resume to retry this section.', 'updated_at' => now(),
        ]);
        Log::error('Chapter section generation failed', ['revision_id' => $this->revisionId, 'stage' => $this->stage, 'exception_type' => $exception ? get_class($exception) : null]);
    }
}
