<?php

namespace App;

use App\Models\Attempt;
use App\Models\Quiz;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LearningTracker
{
    /** @param array{q: string, o: array, c: int, explanation?: string} $question */
    public function remember(array $question, ?int $subjectId = null): int
    {
        $snapshot = ['q' => $question['q'], 'o' => $question['o'], 'c' => $question['c'], 'explanation' => $question['explanation'] ?? ''];
        $fingerprint = hash('sha256', json_encode([$snapshot['q'], $snapshot['o'], $snapshot['c']], JSON_THROW_ON_ERROR));
        DB::table('learning_questions')->insertOrIgnore(['fingerprint' => $fingerprint, 'subject_id' => $subjectId, 'question' => json_encode($snapshot, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);

        return (int) DB::table('learning_questions')->where('fingerprint', $fingerprint)->value('id');
    }

    public function record(User $user, array $questions, array $answers, ?int $subjectId = null, ?string $activityDate = null): void
    {
        foreach ($questions as $index => $question) {
            $questionId = $question['learning_id'] ?? $this->remember($question, $question['subject_id'] ?? $subjectId);
            $answer = (int) ($answers[$index] ?? -1);
            $correct = $answer === $question['c'];
            DB::table('learning_progress')->insertOrIgnore(['user_id' => $user->id, 'question_id' => $questionId, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('learning_progress')->where('user_id', $user->id)->where('question_id', $questionId)->update(['wrong' => ! $correct, 'last_answer' => $answer, 'updated_at' => now()]);
            if ($answer >= 0) {
                DB::table('learning_activity')->insertOrIgnore(['user_id' => $user->id, 'question_id' => $questionId, 'activity_date' => $activityDate ?? now('Asia/Kolkata')->toDateString(), 'correct' => $correct, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function importHistory(User $user): void
    {
        DB::table('learning_preferences')->insertOrIgnore(['user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::transaction(function () use ($user): void {
            $preference = DB::table('learning_preferences')->where('user_id', $user->id)->lockForUpdate()->first();
            if ($preference->history_imported) {
                return;
            }
            $existing = DB::table('learning_progress')->where('user_id', $user->id)->whereNotNull('last_answer')->get();
            $history = collect();
            foreach (Attempt::where('user_id', $user->id)->get() as $attempt) {
                $quiz = Quiz::with('chapter')->find($attempt->quiz_id);
                if ($quiz && count($quiz->questions) === count($attempt->answers)) {
                    $history->push(['questions' => $quiz->questions, 'answers' => $attempt->answers, 'subject' => $quiz->learningSubject()->id, 'time' => $attempt->created_at]);
                }
            }
            foreach (DB::table('daily_quiz_attempts')->where('user_id', $user->id)->get() as $attempt) {
                $history->push(['questions' => json_decode($attempt->questions, true), 'answers' => json_decode($attempt->answers, true), 'subject' => null, 'time' => Carbon::parse($attempt->created_at)]);
            }
            foreach ($history->sortBy(fn (array $item): int => $item['time']->timestamp) as $item) {
                $this->record($user, $item['questions'], $item['answers'], $item['subject'], $item['time']->copy()->timezone('Asia/Kolkata')->toDateString());
            }
            foreach ($existing as $progress) {
                DB::table('learning_progress')->where('id', $progress->id)->update(['wrong' => $progress->wrong, 'last_answer' => $progress->last_answer, 'updated_at' => $progress->updated_at]);
            }
            DB::table('learning_preferences')->where('id', $preference->id)->update(['history_imported' => true]);
        });
    }

    public function resolve(string $source): int
    {
        $parts = explode(':', $source);
        abort_unless(count($parts) >= 2 && ctype_digit($parts[1]), 404);
        if ($parts[0] === 'question') {
            abort_unless(DB::table('learning_questions')->where('id', $parts[1])->exists(), 404);

            return (int) $parts[1];
        }
        abort_unless(count($parts) === 3 && ctype_digit($parts[2]), 404);
        if ($parts[0] === 'quiz') {
            $quiz = Quiz::with('chapter')->findOrFail($parts[1]);
            abort_unless($quiz->isPublished(), 404);
            $question = $quiz->questions[(int) $parts[2]] ?? null;
            abort_unless($question, 404);

            return $this->remember($question, $quiz->learningSubject()->id);
        }
        abort_unless($parts[0] === 'practice', 404);
        $set = DB::table('practice_sets')->find($parts[1]);
        abort_unless($set, 404);
        $question = json_decode($set->questions, true)[(int) $parts[2]] ?? null;
        abort_unless($question, 404);

        return $this->remember($question, $question['subject_id'] ?? null);
    }

    /** @return list<int> */
    public function importBank(): array
    {
        $questionIds = [];
        foreach (Quiz::with('chapter')->published()->get() as $quiz) {
            foreach ($quiz->questions as $question) {
                if (Validator::make($question, ['q' => 'required|string', 'o' => 'required|array|size:4', 'c' => 'required|integer|between:0,3'])->passes()) {
                    $questionIds[] = $this->remember($question, $quiz->learningSubject()->id);
                }
            }
        }
        foreach (DB::table('practice_questions')->where('source', 'gemini')->get() as $row) {
            $questionIds[] = $this->remember(json_decode($row->question, true), $row->subject_id);
        }

        return array_values(array_unique($questionIds));
    }

    public function dashboard(User $user): array
    {
        $today = now('Asia/Kolkata')->toDateString();
        $preference = DB::table('learning_preferences')->where('user_id', $user->id)->first();
        $target = $preference?->daily_target ?? 10;
        $activity = DB::table('learning_activity')->where('user_id', $user->id)->select('activity_date')->selectRaw('COUNT(*) as total')->groupBy('activity_date')->get()->keyBy('activity_date');
        $cursor = now('Asia/Kolkata')->startOfDay();
        if (($activity[$today]->total ?? 0) < $target) {
            $cursor->subDay();
        }
        $streak = 0;
        while (($activity[$cursor->toDateString()]->total ?? 0) >= $target) {
            $streak++;
            $cursor->subDay();
        }
        $subjects = DB::table('learning_activity as a')->join('learning_questions as q', 'q.id', '=', 'a.question_id')->join('subjects as s', 's.id', '=', 'q.subject_id')->where('a.user_id', $user->id)->select('s.id', 's.name', 's.slug')->selectRaw('COUNT(*) as total, SUM(a.correct) as correct')->groupBy('s.id', 's.name', 's.slug')->get()->sortBy(fn ($row): float => $row->correct / $row->total)->values();

        return ['target' => $target, 'language' => $preference?->language ?? 'en', 'exam' => $preference?->exam ?? 'general', 'todayCount' => $activity[$today]->total ?? 0, 'streak' => $streak, 'subjects' => $subjects, 'wrongCount' => DB::table('learning_progress')->where('user_id', $user->id)->where('wrong', true)->count(), 'bookmarkCount' => DB::table('learning_progress')->where('user_id', $user->id)->where('bookmarked', true)->count()];
    }
}
