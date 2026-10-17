<?php

namespace App\Console\Commands;

use App\ChapterContentWorkflow;
use App\Models\Chapter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('chapters:audit {--subject= : Subject slug} {--json : Output the complete inventory as JSON}')]
#[Description('Read-only subject-wise chapter inventory and content quality flags')]
class AuditChapterContent extends Command
{
    public function handle(ChapterContentWorkflow $workflow): int
    {
        $rows = [];
        $query = Chapter::with(['subject', 'quizzes'])->orderBy('subject_id')->orderBy('id');
        if ($this->option('subject')) {
            $query->whereHas('subject', fn ($subject) => $subject->where('slug', $this->option('subject')));
        }
        foreach ($query->lazy(100) as $chapter) {
            $audit = $workflow->audit($chapter);
            $rows[] = ['subject' => $chapter->subject->name, 'id' => $chapter->id, 'chapter' => $chapter->title, 'status' => $chapter->status, 'words' => $audit['words'], 'issues' => implode('; ', $audit['issues'])];
        }
        if ($this->option('json')) {
            $this->line(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        } else {
            $this->table(['Subject', 'ID', 'Chapter', 'Status', 'Words', 'Review flags'], $rows);
        }

        return self::SUCCESS;
    }
}
