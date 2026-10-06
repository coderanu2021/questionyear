<?php

namespace App;

use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class PracticeQuestionGenerator
{
    public const COUNTS = ['daily' => 10, 'weekly' => 50, 'monthly' => 200];

    public function date(string $period): string
    {
        $date = now('Asia/Kolkata');

        return match ($period) {
            'weekly' => $date->startOfWeek()->toDateString(),
            'monthly' => $date->startOfMonth()->toDateString(),
            default => $date->toDateString(),
        };
    }

    public function generate(string $period): void
    {
        if (! isset(self::COUNTS[$period])) {
            throw new RuntimeException('Unknown practice period.');
        }
        Cache::lock('practice-generation', 3600)->block(5, function () use ($period): void {
            $date = $this->date($period);
            if (DB::table('practice_sets')->where('period', $period)->where('starts_on', $date)->exists()) {
                return;
            }
            foreach (Quiz::with('chapter')->whereHas('chapter', fn ($query) => $query->where('status', 'published'))->get() as $quiz) {
                foreach ($quiz->questions as $question) {
                    $this->store($question, $quiz->chapter->subject_id, 'published');
                }
            }
            $subjects = Subject::orderBy('id')->get();
            if ($subjects->isEmpty()) {
                throw new RuntimeException('Add subjects before generating practice questions.');
            }
            $count = self::COUNTS[$period];
            $offset = now('Asia/Kolkata')->dayOfYear % $subjects->count();
            $subjects = $subjects->slice($offset)->concat($subjects->take($offset))->values();
            $selected = collect();
            $fresh = collect();
            $allocated = 0;
            foreach ($subjects as $index => $subject) {
                $target = intdiv($count, $subjects->count()) + ($index < $count % $subjects->count() ? 1 : 0);
                if ($target === 0) {
                    continue;
                }
                $reuseCount = (int) floor(($allocated + $target) * 0.7) - (int) floor($allocated * 0.7);
                $allocated += $target;
                $saved = DB::table('practice_questions')->where('subject_id', $subject->id)->inRandomOrder()->limit($reuseCount)->get();
                $selected = $selected->concat($saved);
                $missing = $target - $saved->count();
                $exclude = $saved->map(fn ($row): string => json_decode($row->question, true)['q'])->all();
                while ($missing > 0) {
                    $batchSize = min(20, $missing);
                    $questions = $this->requestQuestions($subject->name, $batchSize, $exclude);
                    foreach ($questions as $question) {
                        $hash = hash('sha256', mb_strtolower(trim($question['q'])));
                        if (in_array($question['q'], $exclude, true) || $fresh->contains(fn (array $item): bool => hash('sha256', mb_strtolower(trim($item['question']['q']))) === $hash) || DB::table('practice_questions')->where('fingerprint', $hash)->exists()) {
                            throw new RuntimeException('Gemini returned a duplicate question. Retry generation later.');
                        }
                        $fresh->push(['question' => $question, 'subject_id' => $subject->id]);
                        $this->store($question, $subject->id, 'gemini');
                        $exclude[] = $question['q'];
                    }
                    $missing -= count($questions);
                }
            }
            DB::transaction(function () use ($fresh, $selected, $period, $date): void {
                $questions = $selected->map(fn ($row): array => json_decode($row->question, true))->all();
                foreach ($fresh as $item) {
                    $questions[] = $item['question'];
                }
                shuffle($questions);
                DB::table('practice_sets')->insert(['period' => $period, 'starts_on' => $date, 'questions' => json_encode($questions, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
            });
        });
    }

    /** @param array{q: string, o: list<string>, c: int, explanation: string} $question */
    private function store(array $question, int $subjectId, string $source): void
    {
        if (! $this->valid($question)) {
            return;
        }
        DB::table('practice_questions')->insertOrIgnore(['subject_id' => $subjectId, 'fingerprint' => hash('sha256', mb_strtolower(trim($question['q']))), 'question' => json_encode($question, JSON_THROW_ON_ERROR), 'source' => $source, 'created_at' => now(), 'updated_at' => now()]);
    }

    /** @param array<string, mixed> $question */
    private function valid(array $question): bool
    {
        return Validator::make($question, ['q' => 'required|string|max:2000', 'o' => 'required|array|size:4', 'o.*' => 'required|string|max:1000|distinct', 'c' => 'required|integer|between:0,3', 'explanation' => 'required|string|max:4000'])->passes()
            && array_is_list($question['o'] ?? []) && is_int($question['c'] ?? null);
    }

    /**
     * @param  list<string>  $exclude
     * @return list<array{q: string, o: list<string>, c: int, explanation: string}>
     */
    private function requestQuestions(string $subject, int $count, array $exclude): array
    {
        $key = config('services.gemini.key');
        if (! $key) {
            throw new RuntimeException('Set GEMINI_API_KEY before generating practice questions.');
        }
        $response = Http::withHeaders(['x-goog-api-key' => $key])->connectTimeout(10)->timeout(90)
            ->post('https://generativelanguage.googleapis.com/v1beta/models/'.config('services.gemini.model').':generateContent', [
                'contents' => [['parts' => [['text' => 'Generate exactly '.$count.' accurate English MCQs for '.$subject.'. Four distinct options, one correct answer (c is zero-based), and an explanation. Avoid ambiguous or time-sensitive facts. Do not repeat: '.json_encode($exclude)]]]],
                'generationConfig' => ['responseMimeType' => 'application/json', 'responseSchema' => ['type' => 'ARRAY', 'items' => ['type' => 'OBJECT', 'properties' => ['q' => ['type' => 'STRING'], 'o' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']], 'c' => ['type' => 'INTEGER'], 'explanation' => ['type' => 'STRING']], 'required' => ['q', 'o', 'c', 'explanation']]]],
            ]);
        if (! $response->successful()) {
            throw new RuntimeException('Gemini generation failed (HTTP '.$response->status().'). Existing sets are preserved.');
        }
        $questions = json_decode($response->json('candidates.0.content.parts.0.text', ''), true);
        if (! is_array($questions) || ! array_is_list($questions) || count($questions) !== $count) {
            throw new RuntimeException('Gemini returned an incomplete question batch.');
        }
        foreach ($questions as $question) {
            if (! is_array($question) || ! $this->valid($question)) {
                throw new RuntimeException('Gemini returned an invalid question.');
            }
        }

        return $questions;
    }
}
