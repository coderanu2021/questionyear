<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DailyQuizController extends Controller
{
    public function index(Request $request): View
    {
        abort_if($request->user() && $request->user()->status !== 'active', 403);
        $date = now('Asia/Kolkata')->toDateString();
        $participant = $this->participant($request);
        $maximumSets = $request->user() ? 3 : 1;
        $attempts = DB::table('daily_quiz_attempts')->where('participant', $participant)->where('quiz_date', $date)->orderBy('set_number')->get();
        $questions = [];
        foreach (Quiz::whereHas('chapter', fn ($query) => $query->where('status', 'published'))->orderBy('id')->get() as $quiz) {
            foreach ($quiz->questions as $index => $question) {
                $questions[$quiz->id.':'.$index] = $question;
            }
        }
        uksort($questions, fn (string $a, string $b): int => strcmp(hash('sha256', $date.':'.$a), hash('sha256', $date.':'.$b)));
        $availableSets = min($maximumSets, (int) ceil(count($questions) / 50));
        $setNumber = $attempts->count() + 1;
        $snapshotKey = 'daily_quiz.'.$date.'.'.$participant.'.'.$setNumber;
        $snapshot = [];
        if ($setNumber <= $availableSets) {
            $snapshot = $request->session()->get($snapshotKey);
            if ($snapshot === null) {
                $snapshot = array_slice(array_values($questions), ($setNumber - 1) * 50, 50);
                $request->session()->put($snapshotKey, $snapshot);
            }
        }

        return view('website.daily', [
            'date' => $date,
            'questions' => $snapshot,
            'setNumber' => $setNumber,
            'maximumSets' => $maximumSets,
            'availableSets' => $availableSets,
            'attempts' => $attempts,
            'lastAttempt' => $attempts->last(),
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        abort_if($request->user() && $request->user()->status !== 'active', 403);
        $date = now('Asia/Kolkata')->toDateString();
        $data = $request->validate(['date' => 'required|date_format:Y-m-d|in:'.$date, 'set_number' => 'required|integer|between:1,'.($request->user() ? 3 : 1), 'answers' => 'required|array', 'answers.*' => 'required|integer|between:-1,3']);
        $participant = $this->participant($request);
        $snapshotKey = 'daily_quiz.'.$date.'.'.$participant.'.'.$data['set_number'];
        $questions = $request->session()->get($snapshotKey);
        if (! $questions || ! array_is_list($data['answers']) || count($data['answers']) !== count($questions)) {
            throw ValidationException::withMessages(['answers' => 'Open today’s quiz and submit one answer for each question.']);
        }
        $score = 0;
        foreach ($questions as $index => $question) {
            if ((int) $data['answers'][$index] === $question['c']) {
                $score++;
            }
        }
        DB::transaction(function () use ($request, $date, $participant, $data, $questions, $score): void {
            DB::table('daily_quiz_attempts')->insertOrIgnore([
                'user_id' => $request->user()?->id,
                'participant' => $participant,
                'quiz_date' => $date,
                'set_number' => $data['set_number'],
                'answers' => json_encode(array_map('intval', $data['answers'])),
                'questions' => json_encode($questions),
                'score' => $score,
                'total' => count($questions),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('daily')->with('status', 'Quiz submitted. Your marks and answer review are ready below.');
    }

    private function participant(Request $request): string
    {
        if ($request->user()) {
            return 'user:'.$request->user()->id;
        }
        if (! $request->session()->has('daily_guest_id')) {
            $request->session()->put('daily_guest_id', (string) Str::uuid());
        }

        return 'guest:'.$request->session()->get('daily_guest_id');
    }
}
