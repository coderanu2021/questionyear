<?php

namespace App;

use App\Http\Controllers\ExamNotificationController;
use App\Models\Post;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Support\Collection;

class WebsiteNavigation
{
    public const SUBJECT_GROUPS = [
        'history' => ['History', ['history']], 'geography' => ['Geography', ['geography']],
        'polity' => ['Polity', ['political-science']], 'economics' => ['Economics', ['economics']],
        'science' => ['Science', ['physics', 'chemistry', 'biology', 'environment', 'astronomy']],
        'maths-reasoning' => ['Maths & Reasoning', ['mathematics', 'reasoning', 'data-interpretation', 'computer-science']],
        'english' => ['English', ['english']], 'gk' => ['GK & Culture', ['general-knowledge', 'art-culture']],
    ];

    public const EXAMS = [
        'upsc' => ['UPSC', ['history', 'geography', 'political-science', 'economics', 'environment'], ['upsc', 'civil services'], 'upsc'],
        'ssc' => ['SSC', ['mathematics', 'reasoning', 'english', 'general-knowledge', 'history', 'geography'], ['ssc', 'cgl', 'chsl'], 'ssc'],
        'banking' => ['Banking', ['mathematics', 'reasoning', 'english', 'economics', 'data-interpretation'], ['banking', 'ibps', 'sbi', 'canara'], 'banking'],
        'railways' => ['Railways', ['mathematics', 'reasoning', 'physics', 'general-knowledge'], ['railway', 'railways', 'rrb', 'ntpc'], 'railway'],
        'neet' => ['NEET', ['biology', 'physics', 'chemistry'], ['neet'], null],
        'jee' => ['JEE', ['mathematics', 'physics', 'chemistry'], ['jee'], null],
        'cbse-board' => ['CBSE Board', ['mathematics', 'physics', 'chemistry', 'biology', 'english'], ['cbse'], null],
        'state-psc' => ['State PSC', ['history', 'geography', 'political-science', 'general-knowledge'], ['state psc', 'bpsc', 'uppsc', 'mppsc'], null],
    ];

    private ?Collection $subjectInventory = null;

    private ?Collection $quizInventory = null;

    private ?Collection $postInventory = null;

    public function subjects(): Collection
    {
        return $this->subjectInventory ??= Subject::with(['chapters' => fn ($query) => $query->where('status', 'published')->orderBy('id')])->orderBy('id')->get();
    }

    public function quizzes(): Collection
    {
        return $this->quizInventory ??= Quiz::published()->with(['subject', 'chapter.subject'])->orderByDesc('quiz_date')->orderByDesc('id')->get()->filter(fn (Quiz $quiz): bool => count($quiz->questions ?? []) > 0);
    }

    public function posts(): Collection
    {
        return $this->postInventory ??= Post::whereIn('type', ['blog', 'news'])->where('status', 'published')->where('published_at', '<=', now())->orderByDesc('published_at')->get(['id', 'type', 'title', 'slug', 'excerpt', 'published_at']);
    }

    /** @param list<string> $terms */
    public function matches(string $text, array $terms): bool
    {
        return preg_match('/\b(?:'.implode('|', array_map(fn (string $term): string => preg_quote($term, '/'), $terms)).')\b/iu', $text) === 1;
    }

    /** @param list<string> $slugs */
    public function readySubjects(array $slugs): Collection
    {
        return $this->subjects()->whereIn('slug', $slugs)->filter(fn (Subject $subject): bool => $subject->chapters->contains(fn ($chapter): bool => filled($chapter->content) || filled($chapter->notes) || filled($chapter->study_guide)) || $this->quizzes()->contains(fn (Quiz $quiz): bool => $quiz->learningSubject()->id === $subject->id));
    }

    public function examPosts(string $exam): Collection
    {
        return $this->posts()->filter(fn (Post $post): bool => $this->matches($post->title.' '.$post->excerpt, self::EXAMS[$exam][2]));
    }

    public function examQuizzes(string $exam): Collection
    {
        return $this->quizzes()->filter(fn (Quiz $quiz): bool => $this->matches($quiz->title, self::EXAMS[$exam][2]));
    }

    /** @return list<array{label: string, url: string, description: string}> */
    public function notices(string $exam): array
    {
        $prefixes = match ($exam) {
            'upsc' => ['upsc'], 'ssc' => ['ssc'], 'railways' => ['rrb'], 'banking' => ['canara-bank'], default => []
        };
        $links = [];
        foreach (ExamNotificationController::NOTICES as $slug => $notice) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($slug, $prefix)) {
                    $links[] = ['label' => $notice['title'], 'url' => route('notifications.show', ['exam' => $slug]), 'description' => $notice['status'].' — '.$notice['summary']];
                    break;
                }
            }
        }

        return $links;
    }

    /** @return list<array{label: string, url: string}> */
    public function exams(): array
    {
        $links = [];
        foreach (self::EXAMS as $slug => [$label, $subjects]) {
            $specific = $this->examPosts($slug)->isNotEmpty() || $this->examQuizzes($slug)->isNotEmpty();
            $foundation = ! in_array($slug, ['neet', 'jee', 'cbse-board'], true) && ($this->readySubjects($subjects)->isNotEmpty() || $this->notices($slug) !== []);
            if ($specific || $foundation) {
                $links[] = ['label' => $label, 'url' => route('exam', ['exam' => $slug])];
            }
        }

        return $links;
    }

    public function studyTips(): Collection
    {
        return $this->posts()->where('type', 'blog')->filter(fn (Post $post): bool => $this->matches($post->title.' '.$post->excerpt, ['study tips', 'revision', 'study plan', 'preparation guide', 'time management']));
    }

    /** @return array<string, list<array{label: string, url: string}>> */
    public function menus(): array
    {
        $subjects = [];
        foreach (self::SUBJECT_GROUPS as $slug => [$label, $members]) {
            $subject = $this->subjects()->firstWhere('slug', $members[0]);
            if ($subject || count($members) > 1) {
                $subjects[] = ['label' => $label, 'url' => count($members) > 1 ? route('subjects.group', ['group' => $slug]) : route('subject', ['subject' => $subject->slug])];
            }
        }
        $updates = [['label' => 'News', 'url' => route('news.index')], ['label' => 'Blogs', 'url' => route('blogs.index')]];
        if ($this->studyTips()->isNotEmpty()) {
            $updates[] = ['label' => 'Study Tips', 'url' => route('study-tips')];
        }

        return [
            'Subjects' => $subjects,
            'Practice' => [['label' => 'Daily MCQ', 'url' => route('daily')], ['label' => 'Weekly MCQ', 'url' => route('weekly')], ['label' => 'Monthly MCQ', 'url' => route('monthly')], ['label' => 'Mock Tests', 'url' => route('practice.index', ['mode' => 'mock'])], ['label' => 'Topic-wise Sets', 'url' => route('practice.index')], ['label' => 'Leaderboard', 'url' => route('learning.leaderboard')]],
            'Exams' => $this->exams(),
            'Govt Jobs' => array_map(fn (string $category): array => ['label' => $category === 'all' ? 'Latest Government Jobs' : ExamNotificationController::CATEGORIES[$category], 'url' => $category === 'all' ? route('jobs.index') : route('jobs.category', ['category' => $category])], ['all', 'upcoming', 'admit-cards', 'results', '10th-pass', '12th-pass', 'graduate']),
            'Current Affairs' => [['label' => 'All Current Affairs', 'url' => route('current-affairs')], ['label' => 'Daily Capsule', 'url' => route('current-affairs', ['period' => 'daily'])], ['label' => 'Weekly Capsule', 'url' => route('current-affairs', ['period' => 'weekly'])], ['label' => 'Monthly Question Sets', 'url' => route('current-affairs', ['period' => 'monthly'])]],
            'Updates' => $updates,
        ];
    }
}
