<?php

namespace App\Http\Controllers;

use App\LearningTracker;
use App\Models\Attempt;
use App\Models\Post;
use App\Models\Quiz;
use App\Models\Subject;
use App\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WebsiteController extends Controller
{
    public function historyChapter(Request $request, string $category, string $chapterSlug): View|RedirectResponse
    {
        $subject = Subject::where('slug', 'history')->firstOrFail();
        $chapters = $subject->chapters()->where('status', 'published')->orderBy('id')->get();
        $matches = $chapters->filter(fn ($entry) => Str::slug($entry->category ?? '') === $category && Str::slug($entry->title) === $chapterSlug);
        abort_unless($matches->count() === 1, 404);

        return $this->index($request, $subject->slug, $matches->keys()->first());
    }

    public function quizPage(Request $request, string $subject, Quiz $quiz, string $slug): View|RedirectResponse
    {
        abort_unless($quiz->isPublished() && $quiz->learningSubject()->slug === $subject, 404);
        if ($request->url() !== $quiz->publicUrl()) {
            return redirect()->to($quiz->publicUrl(), 301);
        }
        $chapter = $subject === 'current-affairs' ? null : $quiz->chapter->subject->chapters()->where('status', 'published')->orderBy('id')->pluck('id')->search($quiz->chapter_id);

        return $this->index($request, $subject, $chapter, $quiz->id);
    }

    public function index(Request $request, ?string $subject = null, ?int $chapter = null, ?int $test = null): View|RedirectResponse
    {
        $latestBlogs = $request->routeIs('home')
            ? Post::where('type', 'blog')->where('status', 'published')->where('published_at', '<=', now())->orderByDesc('published_at')->orderByDesc('id')->limit(8)->get()
            : collect();

        if ($request->routeIs('login', 'register') && $request->user()) {
            return redirect()->route($request->user()->role === 'admin' ? 'admin' : 'home');
        }

        $subjects = Subject::with(['chapters' => fn ($q) => $q->where('status', 'published')->orderBy('id'), 'chapters.quizzes'])->orderBy('id')->get();
        $curriculum = ['S' => [], 'QB' => [], 'LN' => [], 'tests' => [], 'categories' => [], 'chapterUrls' => [], 'quizUrls' => []];
        $quizIds = [];
        foreach ($subjects as $item) {
            if ($item->slug === 'current-affairs') {
                $quizzes = Quiz::published()->where(fn ($query) => $query->where('subject_id', $item->id)->orWhereHas('chapter', fn ($query) => $query->where('subject_id', $item->id)))->orderByDesc('quiz_date')->orderByDesc('id')->get();
                $curriculum['S'][] = [$item->name, $item->category, $item->description ?? '', $quizzes->pluck('title')->all()];
                foreach ($quizzes as $index => $quiz) {
                    $key = $item->slug.':'.$index;
                    $curriculum['tests'][$key] = [['id' => $quiz->id, 'title' => $quiz->title, 'quiz_date' => $quiz->quiz_date?->toDateString(), 'date_label' => $quiz->quiz_date?->format('d M Y'), 'current_affairs' => true, 'duration' => $quiz->duration, 'pass' => $quiz->passing_score, 'count' => count($quiz->questions), 'url' => $quiz->publicUrl()]];
                    $curriculum['QB'][$key] = [];
                    $quizIds[$key] = $quiz->id;
                    $curriculum['quizUrls'][$key] = $quiz->publicUrl();
                }

                continue;
            }
            $curriculum['S'][] = [$item->name, $item->category, $item->description ?? '', $item->chapters->pluck('title')->all()];
            foreach ($item->chapters as $index => $entry) {
                $key = $item->slug.':'.$index;
                $curriculum['categories'][$key] = $entry->category;
                $entry->setRelation('subject', $item);
                $curriculum['chapterUrls'][$key] = $entry->readingUrl($index);
                if ($item->slug !== 'current-affairs' && $entry->content) {
                    $curriculum['LN'][$key] = ['sub' => $entry->description ?? '', 'icon' => 'fa-book', 'time' => $entry->lessons.' lessons', 's' => [['h' => $entry->title, 'i' => 'fa-book', 'p' => [$entry->content]]], 'sum' => []];
                } elseif ($item->slug !== 'current-affairs' && $entry->notes) {
                    $curriculum['LN'][$key] = $entry->notes;
                }
                $curriculum['tests'][$key] = $entry->quizzes->map(fn ($q) => ['id' => $q->id, 'title' => $q->title, 'quiz_date' => $q->quiz_date?->toDateString(), 'date_label' => $q->quiz_date?->format('d M Y'), 'duration' => $q->duration, 'pass' => $q->passing_score, 'count' => count($q->questions), 'url' => $q->publicUrl()])->all();
                $selectedTest = $test ?? ($request->routeIs('quiz') ? $request->integer('test') : null);
                $quiz = $selectedTest && $subject === $item->slug && $chapter === $index ? $entry->quizzes->firstWhere('id', $selectedTest) : $entry->quizzes->first();
                if ($quiz) {
                    $curriculum['QB'][$key] = array_map(fn ($q) => [$q['q'], $q['o'], null, ''], $quiz->questions);
                    $quizIds[$key] = $quiz->id;
                    $curriculum['quizUrls'][$key] = $quiz->publicUrl();
                }
            }
        }
        if ($subject !== null) {
            $selected = $subjects->firstWhere('slug', $subject);
            abort_unless($selected, 404);
            if ($chapter !== null) {
                abort_unless(isset($selected->chapters[$chapter]), 404);
                $key = $subject.':'.$chapter;
                abort_if($request->routeIs('learn', 'chapter') && ! isset($curriculum['LN'][$key]), 404);
                abort_if($request->is('quiz/*') && ! isset($curriculum['QB'][$key]), 404);
                if ($request->routeIs('quiz')) {
                    return redirect()->to($curriculum['quizUrls'][$key], 301);
                }
                if ($request->routeIs('learn') && $selected->slug === 'history' && in_array($selected->chapters[$chapter]->category, ['Ancient History', 'Medieval History', 'Modern History'], true)) {
                    return redirect()->to($selected->chapters[$chapter]->readingUrl($chapter), 301);
                }
            }
        }
        $seoChapter = isset($selected) && $chapter !== null ? $selected->chapters[$chapter] : null;
        $settings = SiteSettings::values();
        $defaultTitle = match (true) {
            $subject === 'current-affairs' && $test !== null => $quizzes->firstWhere('id', $test)?->title.' – '.$settings['site_title'],
            $seoChapter !== null => $seoChapter->title.' – '.$settings['site_title'],
            $request->routeIs('login') => 'Log in – '.$settings['site_title'],
            $request->routeIs('register') => 'Create account – '.$settings['site_title'],
            isset($selected) => $selected->name.($selected->slug === 'current-affairs' ? ' quizzes – ' : ' chapters – ').$settings['site_title'],
            default => $settings['site_title'],
        };
        $seoTitle = $seoChapter?->meta_title ?: $defaultTitle;
        $seoDescription = $seoChapter?->meta_description ?: ($seoChapter?->description ?: $settings['site_description']);
        $seoKeywords = $seoChapter?->meta_keywords;
        $popular = [];
        foreach ($curriculum['tests'] as $key => $tests) {
            foreach ($tests as $test) {
                $popular[] = $test;
            }
        }

        return view('website.index', ['currentAffairsQuiz' => $subject === 'current-affairs' && $request->routeIs('quiz.show') ? $request->route('quiz') : null, 'seoChapter' => $seoChapter, 'latestBlogs' => $latestBlogs, 'chapterHeading' => $request->routeIs('learn', 'chapter') ? $seoChapter?->title : null, 'chapterMetaTitle' => $seoChapter?->meta_title, 'seoTitle' => $seoTitle, 'seoDescription' => $seoDescription, 'seoKeywords' => $seoKeywords, 'curriculum' => $curriculum, 'quizIds' => $quizIds, 'currentUser' => $request->user()?->only('name', 'email'), 'popular' => $popular, 'chapterCount' => $subjects->sum(fn ($s) => $s->chapters->count()), 'subjectCount' => $subjects->count(), 'questionCount' => Quiz::published()->multipleChoice()->get()->sum(fn ($q) => count($q->questions))]);
    }

    public function answer(Request $request, Quiz $quiz): JsonResponse
    {
        abort_unless($quiz->isPublished() && ! $quiz->isCurrentAffairs(), 404);
        $data = $request->validate(['question' => 'required|integer|min:0', 'answer' => 'required|integer|between:0,3']);
        $question = $quiz->questions[$data['question']] ?? null;
        abort_unless($question, 404);
        abort_if($request->user()?->status === 'blocked', 403);
        if (! $request->user()) {
            $this->consumeGuestQuestions($request, 1);
            $request->session()->put('guest_answers.'.$quiz->id.'.'.$data['question'], $data['answer']);
        } else {
            app(LearningTracker::class)->record($request->user(), [$question], [$data['answer']], $quiz->learningSubject()->id);
        }

        return response()->json(['correct' => $question['c'], 'explanation' => $question['explanation'] ?? '', 'guest_remaining' => $request->user() ? null : max(0, 25 - $request->session()->get('guest_questions_used', 0))]);
    }

    public function attempt(Request $request, Quiz $quiz): JsonResponse
    {
        abort_unless($quiz->isPublished() && ! $quiz->isCurrentAffairs(), 404);
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
        if ($request->user()) {
            app(LearningTracker::class)->record($request->user(), $quiz->questions, $data['answers'], $quiz->learningSubject()->id);
        }

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
