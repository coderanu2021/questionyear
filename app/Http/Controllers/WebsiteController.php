<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WebsiteController extends Controller
{
    public function index(Request $request, ?string $subject = null, ?int $chapter = null): View|RedirectResponse
    {
        if ($request->routeIs('login', 'register') && $request->user()) {
            return redirect()->route($request->user()->role === 'admin' ? 'admin' : 'home');
        }

        $subjects = Subject::with(['chapters' => fn ($q) => $q->where('status', 'published')->orderBy('id'), 'chapters.quizzes'])->orderBy('id')->get();
        $curriculum = ['S' => [], 'QB' => [], 'LN' => [], 'tests' => []];
        $quizIds = [];
        foreach ($subjects as $item) {
            $curriculum['S'][] = [$item->name, $item->category, $item->description ?? '', $item->chapters->pluck('title')->all()];
            foreach ($item->chapters as $index => $entry) {
                $key = $item->slug.':'.$index;
                if ($entry->content) {
                    $curriculum['LN'][$key] = ['sub' => $entry->description ?? '', 'icon' => 'fa-book', 'time' => $entry->lessons.' lessons', 's' => [['h' => $entry->title, 'i' => 'fa-book', 'p' => [$entry->content]]], 'sum' => []];
                } elseif ($entry->notes) {
                    $curriculum['LN'][$key] = $entry->notes;
                }
                $curriculum['tests'][$key] = $entry->quizzes->map(fn ($q) => ['id' => $q->id, 'title' => $q->title, 'duration' => $q->duration, 'pass' => $q->passing_score, 'count' => count($q->questions)])->all();
                $quiz = $request->integer('test') && $request->is('quiz/'.$item->slug.'/'.$index) ? $entry->quizzes->firstWhere('id', $request->integer('test')) : $entry->quizzes->first();
                if ($quiz) {
                    $curriculum['QB'][$key] = array_map(fn ($q) => [$q['q'], $q['o'], null, ''], $quiz->questions);
                    $quizIds[$key] = $quiz->id;
                }
            }
        }
        if ($subject !== null) {
            $selected = $subjects->firstWhere('slug', $subject);
            abort_unless($selected, 404);
            if ($chapter !== null) {
                abort_unless(isset($selected->chapters[$chapter]), 404);
                $key = $subject.':'.$chapter;
                abort_if($request->is('learn/*') && ! isset($curriculum['LN'][$key]), 404);
                abort_if($request->is('quiz/*') && ! isset($curriculum['QB'][$key]), 404);
            }
        }
        $popular = [];
        foreach ($curriculum['tests'] as $key => $tests) {
            foreach ($tests as $test) {
                [$slug, $index] = explode(':', $key);
                $popular[] = [...$test, 'url' => route('quiz', ['subject' => $slug, 'chapter' => $index, 'test' => $test['id']])];
            }
        }

        return view('website.index', ['curriculum' => $curriculum, 'quizIds' => $quizIds, 'currentUser' => $request->user()?->only('name', 'email'), 'popular' => $popular, 'chapterCount' => $subjects->sum(fn ($s) => $s->chapters->count()), 'subjectCount' => $subjects->count(), 'questionCount' => Quiz::whereHas('chapter', fn ($q) => $q->where('status', 'published'))->get()->sum(fn ($q) => count($q->questions))]);
    }

    public function answer(Request $request, Quiz $quiz): JsonResponse
    {
        abort_unless($quiz->chapter->status === 'published', 404);
        $data = $request->validate(['question' => 'required|integer|min:0', 'answer' => 'required|integer|between:0,3']);
        $question = $quiz->questions[$data['question']] ?? null;
        abort_unless($question, 404);
        abort_if($request->user()?->status === 'blocked', 403);
        if (! $request->user()) {
            $this->consumeGuestQuestions($request, 1);
            $request->session()->put('guest_answers.'.$quiz->id.'.'.$data['question'], $data['answer']);
        }

        return response()->json(['correct' => $question['c'], 'explanation' => $question['explanation'] ?? '', 'guest_remaining' => $request->user() ? null : max(0, 25 - $request->session()->get('guest_questions_used', 0))]);
    }

    public function attempt(Request $request, Quiz $quiz): JsonResponse
    {
        abort_unless($quiz->chapter->status === 'published', 404);
        abort_if($request->user()?->status === 'blocked', 403);
        $data = $request->validate(['answers' => 'required|array|size:'.count($quiz->questions), 'answers.*' => 'required|integer|between:-1,3', 'seconds' => 'required|integer|min:0|max:86400']);
        if (! array_is_list($data['answers'])) {
            throw ValidationException::withMessages(['answers' => 'Answers must be an ordered list.']);
        }
        if (! $request->user()) {
            $recorded = $request->session()->get('guest_answers.'.$quiz->id, []);
            $additional = 0;
            foreach ($data['answers'] as $index => $answer) {
                if ($answer >= 0 && (! array_key_exists($index, $recorded) || $recorded[$index] !== $answer)) {
                    $additional++;
                }
            }
            $this->consumeGuestQuestions($request, $additional);
            $request->session()->forget('guest_answers.'.$quiz->id);
        }
        $score = 0;
        foreach ($quiz->questions as $index => $question) {
            if ($data['answers'][$index] === $question['c']) {
                $score++;
            }
        }
        $percentage = (int) round($score / count($quiz->questions) * 100);
        Attempt::create(['quiz_id' => $quiz->id, 'user_id' => $request->user()?->id, 'answers' => $data['answers'], 'score' => $score, 'total' => count($quiz->questions), 'seconds' => $data['seconds'], 'percentage' => $percentage]);

        $review = $quiz->questions;
        if (! $request->user()) {
            foreach ($review as $index => &$question) {
                if ($data['answers'][$index] < 0) {
                    $question['c'] = null;
                    $question['explanation'] = '';
                }
            }
            unset($question);
        }

        return response()->json(['score' => $score, 'percentage' => $percentage, 'passed' => $percentage >= $quiz->passing_score, 'review' => $review]);
    }

    private function consumeGuestQuestions(Request $request, int $amount): void
    {
        $used = (int) $request->session()->get('guest_questions_used', 0);
        if ($used + $amount > 25) {
            abort(response()->json([
                'message' => 'You have used your 25 free questions. Create an account or log in to continue.',
                'code' => 'ACCOUNT_REQUIRED',
                'register_url' => route('register'),
                'login_url' => route('login'),
            ], 403));
        }
        $request->session()->put('guest_questions_used', $used + $amount);
    }
}
