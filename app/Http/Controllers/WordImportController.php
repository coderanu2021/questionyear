<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\Subject;
use App\WordDocumentImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WordImportController extends Controller
{
    public function store(Request $request, WordDocumentImporter $importer): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin' && $request->user()?->status === 'active', 403);
        $data = $request->validate([
            'file' => 'required|file|max:10240|extensions:docx',
            'mode' => 'required|in:notes,mcq,qa',
            'title' => 'required|string|max:255',
            'subject_id' => 'required_if:mode,notes|nullable|integer|exists:subjects,id',
            'chapter_id' => 'required_if:mode,mcq|nullable|integer|exists:chapters,id',
            'qa_subject' => 'required_if:mode,qa|nullable|in:current-affairs,general-knowledge',
            'category' => 'nullable|in:Ancient History,Medieval History,Modern History',
        ]);
        $document = $importer->extract($request->file('file')->getRealPath());
        $questions = $data['mode'] === 'notes' ? [] : $importer->questions($document['lines'], $data['mode']);
        $record = DB::transaction(function () use ($data, $document, $questions) {
            if ($data['mode'] === 'notes') {
                $subject = Subject::findOrFail($data['subject_id']);
                if (in_array($subject->slug, ['current-affairs', 'general-knowledge'], true)) {
                    throw ValidationException::withMessages(['subject_id' => 'Choose a chapter subject for notes.']);
                }

                return Chapter::create(['subject_id' => $subject->id, 'title' => $data['title'], 'content' => $document['html'], 'lessons' => 1, 'status' => 'draft', 'category' => $subject->slug === 'history' ? ($data['category'] ?? null) : null]);
            }
            $chapter = $data['mode'] === 'mcq' ? Chapter::with('subject')->findOrFail($data['chapter_id']) : null;
            if ($chapter && in_array($chapter->subject->slug, ['current-affairs', 'general-knowledge'], true)) {
                throw ValidationException::withMessages(['chapter_id' => 'Choose a chapter that supports MCQs.']);
            }
            $subject = $data['mode'] === 'qa' ? Subject::firstOrCreate(['slug' => $data['qa_subject']], ['name' => $data['qa_subject'] === 'current-affairs' ? 'Current Affairs' : 'General Knowledge', 'category' => 'General', 'description' => 'Questions and answers']) : null;

            return Quiz::create(['chapter_id' => $chapter?->id, 'subject_id' => $subject?->id, 'title' => $data['title'], 'questions' => $questions, 'duration' => 15, 'passing_score' => 40, 'status' => 'draft']);
        });
        $message = $data['mode'] === 'notes' ? 'Word content saved as a draft chapter.' : count($questions).' questions imported. Review them before publishing. MCQs follow the selected chapter’s publication status.';
        if ($document['has_images']) {
            $message .= ' This file contains images; only text and tables were imported.';
        }

        return redirect()->route('admin', ['page' => 'import'])->with('status', $message)->with('imported_url', $data['mode'] === 'notes' ? route('admin.chapters.edit', $record) : route('admin.tests.edit', $record));
    }
}
