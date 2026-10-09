<?php

namespace App\Http\Controllers;

use App\LearningTracker;
use App\PracticeQuestionGenerator;
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
        $period = $request->route()->defaults['period'] ?? 'daily';
        $generator = app(PracticeQuestionGenerator::class);
        $stored = $request->route('set') !== null
            ? DB::table('practice_sets')->where('period', $period)->where('id', $request->route('set'))->first()
            : DB::table('practice_sets')->where('period', $period)->where('starts_on', $generator->date($period))->orderByDesc('set_number')->first();
        abort_if($request->route('set') !== null && ! $stored, 404);
        $date = $stored?->starts_on ?? $generator->date($period);
        $participant = $this->participant($request);
        $attempts = DB::table('daily_quiz_attempts')->where('period', $period)->where('participant', $participant)->where('quiz_date', $date)->orderBy('set_number')->get();
        $questions = $stored ? json_decode($stored->questions, true) : [];
        $availableSets = DB::table('practice_sets')->where('period', $period)->where('starts_on', $date)->count();
        $setNumber = $stored?->set_number ?? 1;
        $lastAttempt = $attempts->firstWhere('set_number', $setNumber);
        $snapshotKey = 'practice_quiz.'.$period.'.'.$date.'.'.$participant.'.'.$setNumber;
        $snapshot = [];
        if ($stored && ! $lastAttempt) {
            $snapshot = $request->session()->get($snapshotKey);
            if ($snapshot === null) {
                $snapshot = $questions;
                $request->session()->put($snapshotKey, $snapshot);
            }
        }

        return view('website.daily', [
            'date' => $date,
            'practiceSetId' => $stored?->id,
            'period' => $period,
            'questionCount' => PracticeQuestionGenerator::COUNTS[$period],
            'questions' => $snapshot,
            'setNumber' => $setNumber,
            'maximumSets' => $availableSets,
            'availableSets' => $availableSets,
            'attempts' => $attempts,
            'lastAttempt' => $lastAttempt,
            'archiveSets' => DB::table('practice_sets')->where('period', $period)->orderByDesc('starts_on')->orderByDesc('set_number')->paginate(12, ['id', 'starts_on', 'set_number', 'created_at']),
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        abort_if($request->user() && $request->user()->status !== 'active', 403);
        $period = $request->route()->defaults['period'] ?? 'daily';
        $stored = $request->route('set') !== null
            ? DB::table('practice_sets')->where('period', $period)->where('id', $request->route('set'))->first()
            : null;
        abort_if($request->route('set') !== null && ! $stored, 404);
        $date = $stored?->starts_on ?? app(PracticeQuestionGenerator::class)->date($period);
        $data = $request->validate(['date' => 'required|date_format:Y-m-d|in:'.$date, 'set_number' => 'required|integer|min:1'.($stored ? '|in:'.$stored->set_number : ''), 'answers' => 'required|array', 'answers.*' => 'required|integer|between:-1,3']);
        if (! $stored) {
            $stored = DB::table('practice_sets')->where('period', $period)->where('starts_on', $date)->where('set_number', $data['set_number'])->first();
            if (! $stored) {
                throw ValidationException::withMessages(['set_number' => 'This quiz set is not available.']);
            }
        }
        $participant = $this->participant($request);
        $snapshotKey = 'practice_quiz.'.$period.'.'.$date.'.'.$participant.'.'.$data['set_number'];
        $questions = $request->session()->get($snapshotKey);
        if (! $questions || ! array_is_list($data['answers']) || count($data['answers']) !== count($questions)) {
            throw ValidationException::withMessages(['answers' => 'Open this quiz set and submit one answer for each question.']);
        }
        $score = 0;
        foreach ($questions as $index => $question) {
            if ((int) $data['answers'][$index] === $question['c']) {
                $score++;
            }
        }
        DB::transaction(function () use ($request, $period, $date, $participant, $data, $questions, $score): void {
            $inserted = DB::table('daily_quiz_attempts')->insertOrIgnore([
                'user_id' => $request->user()?->id,
                'participant' => $participant,
                'quiz_date' => $date,
                'period' => $period,
                'set_number' => $data['set_number'],
                'answers' => json_encode(array_map('intval', $data['answers'])),
                'questions' => json_encode($questions),
                'score' => $score,
                'total' => count($questions),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($inserted && $request->user()) {
                app(LearningTracker::class)->record($request->user(), $questions, $data['answers']);
            }
        });

        return redirect()->route($request->route('set') !== null ? $period.'.set' : $period, $request->route('set') !== null ? ['set' => $stored->id] : [])->with('status', 'Quiz submitted. Your marks and answer review are ready below.');
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
