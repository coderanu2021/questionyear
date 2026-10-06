<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Chapter;
use App\Models\Message;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin' && $request->user()?->status === 'active', 403);
    }

    public function index(Request $request, string $page = 'dashboard'): View
    {
        $this->authorizeAdmin($request);
        abort_unless(in_array($page, ['dashboard', 'chapters', 'tests', 'users', 'analytics', 'messages', 'settings']), 404);

        return view('admin.index', ['state' => $this->state(), 'page' => $page]);
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

                return ['id' => $q->id, 'ch' => $q->chapter_id, 'title' => $q->title, 'dur' => $q->duration, 'pass' => $q->passing_score, 'qs' => $q->questions, 'attempts' => $results->count(), 'avg' => (int) round($results->avg('percentage') ?? 0)];
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
            'tests.*.id' => 'required|integer|min:1|distinct', 'tests.*.ch' => 'required|integer', 'tests.*.title' => 'required|string|max:255', 'tests.*.dur' => 'required|integer|between:1,240', 'tests.*.pass' => 'required|integer|between:1,100', 'tests.*.qs' => 'required|array|min:1|max:200', 'tests.*.qs.*.q' => 'required|string|max:5000', 'tests.*.qs.*.o' => 'required|array|size:4', 'tests.*.qs.*.o.*' => 'required|string|max:2000', 'tests.*.qs.*.c' => 'required|integer|between:0,3', 'tests.*.qs.*.explanation' => 'nullable|string|max:10000',
            'users.*.id' => 'required|integer|exists:users,id', 'users.*.status' => 'required|in:active,inactive,blocked',
        ]);
        DB::transaction(function () use ($data) {
            abort_unless(hash_equals($this->state()['version'], $data['version']), 409, 'Data changed in another tab. Reload before saving.');
            $chapterIds = array_column($data['chapters'], 'id');
            foreach ($data['tests'] as $test) {
                if (! in_array($test['ch'], $chapterIds)) {
                    throw ValidationException::withMessages(['tests' => 'Select an existing chapter for every test.']);
                }
            }
            Quiz::whereNotIn('id', array_column($data['tests'], 'id'))->delete();
            Chapter::whereNotIn('id', $chapterIds)->delete();
            foreach ($data['chapters'] as $chapter) {
                $subject = Subject::firstOrCreate(['name' => $chapter['subject']], ['slug' => Str::slug(str_replace('&', '', $chapter['subject'])), 'category' => 'General', 'description' => 'Explore '.$chapter['subject']]);
                $record = Chapter::find($chapter['id']) ?? new Chapter;
                $record->id = $chapter['id'];
                $record->fill(['subject_id' => $subject->id, 'category' => $subject->slug === 'history' ? (array_key_exists('category', $chapter) ? $chapter['category'] : $record->category) : null, 'title' => $chapter['title'], 'lessons' => $chapter['lessons'], 'status' => $chapter['status'], 'description' => $chapter['desc'] ?? '', 'content' => $this->sanitize($chapter['content'] ?? ''), 'meta_title' => array_key_exists('meta_title', $chapter) ? $chapter['meta_title'] : $record->meta_title, 'meta_description' => array_key_exists('meta_description', $chapter) ? $chapter['meta_description'] : $record->meta_description, 'meta_keywords' => array_key_exists('meta_keywords', $chapter) ? $chapter['meta_keywords'] : $record->meta_keywords])->save();
            }
            foreach ($data['tests'] as $test) {
                $record = Quiz::find($test['id']) ?? new Quiz;
                $record->id = $test['id'];
                $record->fill(['chapter_id' => $test['ch'], 'title' => $test['title'], 'duration' => $test['dur'], 'passing_score' => $test['pass'], 'questions' => $test['qs']])->save();
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
