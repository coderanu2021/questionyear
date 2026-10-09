<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Chapter;
use App\Models\Message;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use App\PracticeQuestionGenerator;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AdminController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin' && $request->user()?->status === 'active', 403);
    }

    public function index(Request $request, string $page = 'dashboard'): View
    {
        $this->authorizeAdmin($request);
        abort_unless(in_array($page, ['dashboard', 'chapters', 'tests', 'users', 'analytics', 'messages', 'settings', 'practice', 'import']), 404);

        $practicePeriods = [];
        if ($page === 'practice') {
            $generator = app(PracticeQuestionGenerator::class);
            foreach (PracticeQuestionGenerator::COUNTS as $period => $count) {
                $date = $generator->date($period);
                $sets = DB::table('practice_sets')->where('period', $period)->where('starts_on', $date);
                $setCount = $sets->count();
                $set = $sets->orderByDesc('set_number')->first();
                $practicePeriods[] = ['period' => $period, 'date' => $date, 'target' => $count, 'count' => $set ? count(json_decode($set->questions, true)) : 0, 'created_at' => $set?->created_at, 'ready' => $set !== null, 'set_count' => $setCount, 'set_id' => $set?->id];
            }
        }

        return view('admin.index', ['state' => $this->state(), 'page' => $page, 'practicePeriods' => $practicePeriods, 'importSubjects' => $page === 'import' ? Subject::whereNotIn('slug', ['current-affairs', 'general-knowledge'])->orderBy('name')->get() : []]);
    }

    public function generatePractice(Request $request, PracticeQuestionGenerator $generator): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['period' => 'required|in:daily,weekly,monthly']);
        try {
            $setId = $generator->generate($data['period'], newSet: true);
        } catch (RuntimeException|ConnectionException|LockTimeoutException $exception) {
            report($exception);
            $message = match (true) {
                $exception instanceof LockTimeoutException => 'Another quiz generation is already running. Wait for it to finish, then refresh this page.',
                $exception instanceof ConnectionException => 'The server could not connect to Gemini or the API request timed out. Try again after checking server connectivity.',
                get_class($exception) === RuntimeException::class => $exception->getMessage(),
                default => 'Generation could not complete. Check the server logs for details. Existing quizzes are preserved.',
            };

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message, 'errors' => ['generation' => [$message]]], 422);
            }

            return redirect()->route('admin', ['page' => 'practice'])->withErrors(['generation' => $message]);
        }

        $message = 'A new '.$data['period'].' quiz is ready. Previous quizzes remain available in the quiz archive.';
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'url' => route($data['period'].'.set', ['set' => $setId])]);
        }

        return redirect()->route('admin', ['page' => 'practice'])->with('status', $message);
    }

    public function chapterPage(Request $request, ?Chapter $chapter = null): View
    {
        $this->authorizeAdmin($request);

        return view('admin.index', [
            'state' => $this->state(),
            'page' => 'chapters',
            'chapterPage' => ['id' => $chapter?->id, 'mode' => $request->routeIs('admin.chapters.show') ? 'show' : 'edit'],
        ]);
    }

    public function testPage(Request $request, ?Quiz $quiz = null): View
    {
        $this->authorizeAdmin($request);

        return view('admin.index', [
            'state' => $this->state(),
            'page' => 'tests',
            'testPage' => ['id' => $quiz?->id],
        ]);
    }

    private function state(): array
    {
        $attempts = Attempt::all();
        $quizzes = Quiz::orderBy('id')->get();
        $users = User::where('role', 'student')->get();
        $weeks = ['labels' => [], 'attempts' => [], 'signups' => []];
        for ($i = 6; $i >= 0; $i--) {
            $start = now()->startOfWeek()->subWeeks($i);
            $end = $start->copy()->addWeek();
            $weeks['labels'][] = $start->format('d M');
            $weeks['attempts'][] = $attempts->where('created_at', '>=', $start)->where('created_at', '<', $end)->count();
            $weeks['signups'][] = $users->where('created_at', '>=', $start)->where('created_at', '<', $end)->count();
        }

        return [
            'chapters' => Chapter::with('subject')->orderBy('id')->get()->map(fn ($c) => ['id' => $c->id, 'title' => $c->title, 'subject' => $c->subject->name, 'category' => $c->category, 'lessons' => $c->lessons, 'status' => $c->status, 'desc' => $c->description ?? '', 'content' => $c->content ?? $this->notesHtml($c->notes), 'meta_title' => $c->meta_title ?? '', 'meta_description' => $c->meta_description ?? '', 'meta_keywords' => $c->meta_keywords ?? ''])->all(),
            'tests' => $quizzes->map(function ($q) use ($attempts) {
                $results = $attempts->where('quiz_id', $q->id);

                return ['id' => $q->id, 'ch' => $q->chapter_id, 'current_affairs' => $q->learningSubject()->slug === 'current-affairs', 'general_knowledge' => $q->learningSubject()->slug === 'general-knowledge', 'status' => in_array($q->learningSubject()->slug, ['current-affairs', 'general-knowledge'], true) ? ($q->isPublished() ? 'published' : 'draft') : $q->chapter->status, 'title' => $q->title, 'quiz_date' => $q->quiz_date?->toDateString(), 'dur' => $q->duration, 'pass' => $q->passing_score, 'qs' => $q->isQuestionAnswer() ? $q->questionAnswers() : $q->questions, 'attempts' => $results->count(), 'avg' => (int) round($results->avg('percentage') ?? 0)];
            })->all(),
            'users' => $users->map(function ($u) use ($attempts, $quizzes) {
                $results = $attempts->where('user_id', $u->id);

                return ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'score' => (int) round($results->avg('percentage') ?? 0), 'done' => $quizzes->whereIn('id', $results->pluck('quiz_id'))->pluck('chapter_id')->unique()->count(), 'joined' => $u->created_at->toDateString(), 'status' => $u->status];
            })->all(),
            'weeks' => $weeks,
            'messages' => Message::latest()->get()->toArray(),
            'version' => hash('sha256', json_encode([Chapter::all()->toArray(), $quizzes->toArray(), $users->map->only(['id', 'status'])->all()])),
        ];
    }

    private function notesHtml(?array $notes): string
    {
        $html = '';
        foreach ($notes['s'] ?? [] as $section) {
            $html .= '<h2>'.e($section['h']).'</h2><p>'.implode('</p><p>', $section['p']).'</p>';
            if (isset($section['t'])) {
                $html .= '<table><thead><tr><th>'.implode('</th><th>', $section['t']['h']).'</th></tr></thead><tbody>';
                foreach ($section['t']['r'] as $row) {
                    $html .= '<tr><td>'.implode('</td><td>', $row).'</td></tr>';
                }
                $html .= '</tbody></table>';
            }
            if (isset($section['n'])) {
                $html .= '<blockquote>'.$section['n']['x'].'</blockquote>';
            }
        }
        if (! empty($notes['sum'])) {
            $html .= '<h2>Summary</h2><ul><li>'.implode('</li><li>', $notes['sum']).'</li></ul>';
        }

        return $html;
    }

    public function save(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'version' => 'required|string', 'chapters' => 'present|array|max:1000', 'tests' => 'present|array|max:1000', 'users' => 'present|array',
            'chapters.*.id' => 'required|integer|min:1|distinct', 'chapters.*.title' => 'required|string|max:255', 'chapters.*.subject' => 'required|string|max:100', 'chapters.*.category' => 'nullable|in:Ancient History,Medieval History,Modern History', 'chapters.*.lessons' => 'required|integer|between:1,1000', 'chapters.*.status' => 'required|in:published,draft', 'chapters.*.desc' => 'nullable|string|max:10000', 'chapters.*.content' => 'nullable|string|max:500000', 'chapters.*.meta_title' => 'nullable|string|max:255', 'chapters.*.meta_description' => 'nullable|string|max:1000', 'chapters.*.meta_keywords' => 'nullable|string|max:1000',
            'tests.*.id' => 'required|integer|min:1|distinct', 'tests.*.ch' => 'nullable|integer', 'tests.*.current_affairs' => 'sometimes|boolean', 'tests.*.general_knowledge' => 'sometimes|boolean', 'tests.*.status' => 'sometimes|in:published,draft', 'tests.*.title' => 'required|string|max:255', 'tests.*.quiz_date' => 'nullable|date_format:Y-m-d', 'tests.*.dur' => 'sometimes|required|integer|between:1,240', 'tests.*.pass' => 'sometimes|required|integer|between:1,100', 'tests.*.qs' => 'required|array|min:1|max:200', 'tests.*.qs.*.q' => 'required|string|max:5000', 'tests.*.qs.*.answer' => 'sometimes|required|string|max:10000', 'tests.*.qs.*.o' => 'sometimes|array|size:4', 'tests.*.qs.*.o.*' => 'required|string|max:2000', 'tests.*.qs.*.c' => 'sometimes|integer|between:0,3', 'tests.*.qs.*.explanation' => 'nullable|string|max:10000',
            'users.*.id' => 'required|integer|exists:users,id', 'users.*.status' => 'required|in:active,inactive,blocked',
        ]);
        foreach ($data['tests'] as $index => $test) {
            if (($test['current_affairs'] ?? false) && ($test['general_knowledge'] ?? false)) {
                throw ValidationException::withMessages(['tests.'.$index.'.general_knowledge' => 'Select one quiz type.']);
            }
            $rules = ($test['current_affairs'] ?? false) || ($test['general_knowledge'] ?? false)
                ? ['qs.*.answer' => 'required|string|max:10000', 'qs.*.o' => 'prohibited', 'qs.*.c' => 'prohibited', 'qs.*.explanation' => 'prohibited']
                : ['dur' => 'required|integer|between:1,240', 'pass' => 'required|integer|between:1,100', 'qs.*.o' => 'required|array|size:4', 'qs.*.c' => 'required|integer|between:0,3'];
            $validator = Validator::make($test, $rules);
            if ($validator->fails()) {
                $errors = [];
                foreach ($validator->errors()->messages() as $key => $messages) {
                    $errors['tests.'.$index.'.'.$key] = $messages;
                }
                throw ValidationException::withMessages($errors);
            }
        }
        DB::transaction(function () use ($data) {
            abort_unless(hash_equals($this->state()['version'], $data['version']), 409, 'Data changed in another tab. Reload before saving.');
            $chapterIds = array_column($data['chapters'], 'id');
            foreach ($data['tests'] as $test) {
                $existingQuiz = Quiz::find($test['id']);
                $standaloneSubject = ($test['current_affairs'] ?? false) ? 'current-affairs' : (($test['general_knowledge'] ?? false) ? 'general-knowledge' : null);
                if ($standaloneSubject !== null && $existingQuiz?->chapter_id !== null && $existingQuiz->learningSubject()->slug !== $standaloneSubject) {
                    throw ValidationException::withMessages(['tests' => 'Create a new standalone quiz instead of changing an existing subject quiz.']);
                }
                if (! ($test['current_affairs'] ?? false) && ! ($test['general_knowledge'] ?? false) && ! in_array($test['ch'] ?? null, $chapterIds, true)) {
                    throw ValidationException::withMessages(['tests' => 'Select an existing chapter for every test.']);
                }
                if (! ($test['current_affairs'] ?? false) && ! ($test['general_knowledge'] ?? false) && collect($data['chapters'])->contains(fn (array $chapter): bool => $chapter['id'] === $test['ch'] && in_array(Str::slug($chapter['subject']), ['current-affairs', 'general-knowledge'], true))) {
                    throw ValidationException::withMessages(['tests' => 'Select Current Affairs or General Knowledge as the quiz type without a chapter.']);
                }
            }
            Quiz::whereNotIn('id', array_column($data['tests'], 'id'))->delete();
            Chapter::whereNotIn('id', $chapterIds)->delete();
            foreach ($data['chapters'] as $chapter) {
                if (in_array(Str::slug($chapter['subject']), ['current-affairs', 'general-knowledge'], true) && ! Chapter::whereKey($chapter['id'])->whereHas('subject', fn ($query) => $query->where('slug', Str::slug($chapter['subject'])))->exists()) {
                    throw ValidationException::withMessages(['chapters' => 'Add Current Affairs or General Knowledge directly from Tests & Quizzes without a chapter.']);
                }
                $subject = Subject::firstOrCreate(['name' => $chapter['subject']], ['slug' => Str::slug(str_replace('&', '', $chapter['subject'])), 'category' => 'General', 'description' => 'Explore '.$chapter['subject']]);
                $record = Chapter::find($chapter['id']) ?? new Chapter;
                $record->id = $chapter['id'];
                $record->fill(['subject_id' => $subject->id, 'category' => $subject->slug === 'history' ? (array_key_exists('category', $chapter) ? $chapter['category'] : $record->category) : null, 'title' => $chapter['title'], 'lessons' => $chapter['lessons'], 'status' => $chapter['status'], 'description' => $chapter['desc'] ?? '', 'content' => $this->sanitize($chapter['content'] ?? ''), 'meta_title' => array_key_exists('meta_title', $chapter) ? $chapter['meta_title'] : $record->meta_title, 'meta_description' => array_key_exists('meta_description', $chapter) ? $chapter['meta_description'] : $record->meta_description, 'meta_keywords' => array_key_exists('meta_keywords', $chapter) ? $chapter['meta_keywords'] : $record->meta_keywords])->save();
            }
            foreach ($data['tests'] as $test) {
                $record = Quiz::find($test['id']) ?? new Quiz;
                $record->id = $test['id'];
                if (($test['current_affairs'] ?? false) || ($test['general_knowledge'] ?? false)) {
                    $previousQuestions = $record->questions ?? [];
                    foreach ($test['qs'] as $index => $question) {
                        $test['qs'][$index] = array_merge($previousQuestions[$index] ?? [], ['q' => $question['q'], 'answer' => $question['answer']]);
                    }
                }
                $record->fill(['chapter_id' => (($test['current_affairs'] ?? false) || ($test['general_knowledge'] ?? false)) ? ($record->chapter_id ?? null) : $test['ch'], 'subject_id' => (($test['current_affairs'] ?? false) || ($test['general_knowledge'] ?? false)) && $record->chapter_id === null ? Subject::firstOrCreate(['slug' => ($test['current_affairs'] ?? false) ? 'current-affairs' : 'general-knowledge'], ['name' => ($test['current_affairs'] ?? false) ? 'Current Affairs' : 'General Knowledge', 'category' => 'General', 'description' => ($test['current_affairs'] ?? false) ? 'Daily current affairs questions and answers' : 'General knowledge MCQs'])->id : null, 'status' => $test['status'] ?? 'published', 'title' => $test['title'], 'quiz_date' => array_key_exists('quiz_date', $test) ? $test['quiz_date'] : $record->quiz_date, 'duration' => $test['dur'] ?? $record->duration ?? 15, 'passing_score' => $test['pass'] ?? $record->passing_score ?? 40, 'questions' => $test['qs']])->save();
            }
            foreach ($data['users'] as $user) {
                User::where('id', $user['id'])->where('role', 'student')->update(['status' => $user['status']]);
            }
        });

        return response()->json($this->state());
    }

    private function sanitize(string $html): string
    {
        $html = preg_replace('/<(script|style|iframe)\b[^>]*>[\s\S]*?<\/\1>/i', '', $html) ?? '';
        $html = strip_tags($html, '<p><br><h2><h3><h4><b><strong><i><em><u><ul><ol><li><blockquote><table><thead><tbody><tr><th><td><figure>');

        return preg_replace('/<([a-z0-9]+)\b[^>]*>/i', '<$1>', $html) ?? '';
    }
}
