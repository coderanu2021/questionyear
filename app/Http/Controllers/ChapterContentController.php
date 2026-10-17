<?php

namespace App\Http\Controllers;

use App\ChapterContentWorkflow;
use App\Jobs\GenerateChapterSection;
use App\Models\Chapter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;

class ChapterContentController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin' && $request->user()?->status === 'active', 403);
    }

    /** @return array<mixed> */
    private function decode(string $json, string $field): array
    {
        try {
            $data = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
            if (is_array($data)) {
                return $data;
            }
        } catch (JsonException) {
        }
        throw ValidationException::withMessages([$field => 'Enter valid JSON.']);
    }

    public function index(Request $request, ChapterContentWorkflow $workflow): View
    {
        $this->authorizeAdmin($request);
        $chapters = Chapter::with(['subject', 'quizzes'])->orderBy('subject_id')->orderBy('id')->get();
        $revisions = DB::table('chapter_content_revisions')->orderByDesc('id')->paginate(25);

        return view('admin.pages.chapter-content', ['chapters' => $chapters, 'audits' => $chapters->mapWithKeys(fn (Chapter $chapter): array => [$chapter->id => $workflow->audit($chapter)]), 'revisions' => $revisions, 'revision' => null]);
    }

    public function show(Request $request, int $revision, ChapterContentWorkflow $workflow): View
    {
        $this->authorizeAdmin($request);
        $entry = $workflow->revision($revision);

        return view('admin.pages.chapter-content', ['revision' => $entry, 'chapter' => Chapter::with('subject')->findOrFail($entry['chapter_id']), 'preview' => $workflow->render($workflow->sanitize($entry['payload']), $entry['brief']['sources'])]);
    }

    public function store(Request $request, ChapterContentWorkflow $workflow): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['batch' => 'required|string|max:2000000', 'mode' => 'required|in:generate,import']);
        $items = $this->decode($data['batch'], 'batch');
        $workflow->validateItems($items);
        if ($data['mode'] === 'import') {
            foreach ($items as $item) {
                $workflow->quality->validate($item['payload'] ?? []);
            }
        }
        $ids = DB::transaction(function () use ($workflow, $items, $request, $data): array {
            $ids = $workflow->start($items, $request->user()->id, $data['mode'] === 'generate');
            if ($data['mode'] === 'import') {
                $byChapter = collect($items)->keyBy('chapter_id');
                foreach ($ids as $id) {
                    $revision = $workflow->revision($id);
                    $workflow->save($id, 1, $byChapter[$revision['chapter_id']]['payload']);
                }
            }

            return $ids;
        });

        return redirect()->route('admin.content.show', $ids[0])->with('status', count($ids).' chapter revisions prepared. Existing public content is preserved.');
    }

    public function update(Request $request, int $revision, ChapterContentWorkflow $workflow): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['version' => 'required|integer', 'payload' => 'required|string|max:2000000', 'brief' => 'sometimes|required|string|max:1000000']);
        $workflow->save($revision, $data['version'], $this->decode($data['payload'], 'payload'), isset($data['brief']) ? $this->decode($data['brief'], 'brief') : null);

        return back()->with('status', 'Saved for review. Previous verification has been cleared.');
    }

    public function verify(Request $request, int $revision, ChapterContentWorkflow $workflow): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'version' => 'required|integer', 'review_note' => 'required|string|min:30|max:10000',
            'facts_checked' => 'accepted', 'syllabus_checked' => 'accepted', 'originality_checked' => 'accepted', 'readability_checked' => 'accepted',
        ]);
        $workflow->verify($revision, $data['version'], $request->user()->id, $data['review_note']);

        return back()->with('status', 'Verified by you. Publishing is a separate action.');
    }

    public function publish(Request $request, int $revision, ChapterContentWorkflow $workflow): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['version' => 'required|integer', 'approve_replacement' => 'sometimes|accepted']);
        $workflow->publish($revision, $data['version'], $request->boolean('approve_replacement'));

        return back()->with('status', 'Verified chapter and practice quiz published on the existing routes.');
    }

    public function resume(Request $request, int $revision, ChapterContentWorkflow $workflow): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['version' => 'required|integer']);
        DB::transaction(function () use ($revision, $workflow, $data): void {
            DB::table('chapter_content_revisions')->where('id', $revision)->lockForUpdate()->first();
            $entry = $workflow->revision($revision);
            abort_unless($entry['version'] === $data['version'] && $entry['status'] === 'draft' && ($entry['generation_state'] === 'failed' || now()->diffInMinutes($entry['updated_at'], absolute: true) > 10), 409, 'Only failed or stalled draft generation can be resumed.');
            DB::table('chapter_content_revisions')->where('id', $revision)->update(['generation_state' => 'queued', 'last_error' => null, 'updated_at' => now()]);
            GenerateChapterSection::dispatch($revision, $entry['payload']['_completed'] ?? 0)->afterCommit();
        });

        return back()->with('status', 'Queued from the last completed section.');
    }

    public function archive(Request $request, int $revision, ChapterContentWorkflow $workflow): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['version' => 'required|integer']);
        DB::transaction(function () use ($revision, $workflow, $data): void {
            DB::table('chapter_content_revisions')->where('id', $revision)->lockForUpdate()->first();
            $entry = $workflow->revision($revision);
            abort_unless($entry['version'] === $data['version'] && in_array($entry['status'], ['draft', 'review', 'verified'], true), 409);
            DB::table('chapter_content_revisions')->where('id', $revision)->update(['status' => 'archived', 'version' => $data['version'] + 1, 'updated_at' => now()]);
        });

        return redirect()->route('admin.content.index')->with('status', 'Working revision archived. Its content and original snapshot are retained. You can now prepare a new brief.');
    }
}
