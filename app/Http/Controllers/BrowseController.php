<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\WebsiteNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BrowseController extends Controller
{
    private function page(string $title, string $description, array $sections, ?array $parent = null): View
    {
        return view('website.browse', compact('title', 'description', 'sections', 'parent'));
    }

    private function subjectLinks(Collection $subjects): array
    {
        return $subjects->map(fn ($subject): array => ['label' => $subject->name, 'url' => route('subject', $subject->slug), 'description' => $subject->description])->values()->all();
    }

    private function quizLinks(Collection $quizzes): array
    {
        return $quizzes->map(fn (Quiz $quiz): array => ['label' => $quiz->title, 'url' => $quiz->publicUrl(), 'description' => count($quiz->questions).' questions'.($quiz->quiz_date ? ' · '.$quiz->quiz_date->format('d M Y') : '')])->values()->all();
    }

    private function postLinks(Collection $posts): array
    {
        return $posts->map(fn ($post): array => ['label' => $post->title, 'url' => route($post->type === 'news' ? 'news.show' : 'blogs.show', $post->slug), 'description' => ucfirst($post->type).' · '.$post->published_at->format('d M Y')])->values()->all();
    }

    public function exams(WebsiteNavigation $navigation): View
    {
        return $this->page('Exams', 'Choose an exam to find available practice, subject notes and published updates.', [['title' => 'Exam hubs', 'links' => $navigation->exams()]]);
    }

    public function exam(string $exam, WebsiteNavigation $navigation): View
    {
        abort_unless(isset(WebsiteNavigation::EXAMS[$exam]), 404);
        [$label, $slugs, , $jobs] = WebsiteNavigation::EXAMS[$exam];
        $subjects = $navigation->readySubjects($slugs);
        $related = $navigation->quizzes()->filter(fn (Quiz $quiz): bool => in_array($quiz->learningSubject()->slug, $slugs, true));
        $sections = [
            ['title' => 'Exam-specific MCQs and mock tests', 'links' => $this->quizLinks($navigation->examQuizzes($exam)->take(12))],
            ['title' => 'Subject notes for foundation revision', 'description' => 'These shared subject resources are not a complete or official exam syllabus.', 'links' => $this->subjectLinks($subjects)],
            ['title' => 'Topic-wise practice', 'links' => $this->quizLinks($related->take(12))],
            ['title' => 'Notifications and reference guides', 'description' => 'Check each notice’s year, dates and status. An archived notice is not a new application opportunity.', 'links' => $navigation->notices($exam)],
            ['title' => 'Preparation guides and related updates', 'links' => $this->postLinks($navigation->examPosts($exam)->take(12))],
        ];
        if ($jobs !== null) {
            $sections[] = ['title' => 'More recruitment updates', 'links' => [['label' => $label.' jobs and notices', 'url' => route('jobs.category', ['category' => $jobs])]]];
        }

        $sections = array_values(array_filter($sections, fn (array $section): bool => $section['links'] !== []));

        return $this->page($label.' preparation hub', 'Find available '.$label.' practice, foundation subjects, preparation articles and recruitment updates in one place.', $sections ?: [['title' => 'Available resources', 'links' => []]], ['label' => 'Exams', 'url' => route('exams.index')]);
    }

    public function subjects(string $group, WebsiteNavigation $navigation): View
    {
        [$label, $slugs] = WebsiteNavigation::SUBJECT_GROUPS[$group];

        return $this->page($label, 'Choose a subject with published notes or practice questions.', [['title' => 'Subjects', 'links' => $this->subjectLinks($navigation->readySubjects($slugs))]]);
    }

    public function practice(Request $request, WebsiteNavigation $navigation): View
    {
        $data = $request->validate(['mode' => 'sometimes|in:mock', 'subject' => 'sometimes|string|max:100']);
        $quizzes = $navigation->quizzes()->reject(fn (Quiz $quiz): bool => $quiz->isQuestionAnswer());
        if (isset($data['subject'])) {
            $quizzes = $quizzes->filter(fn (Quiz $quiz): bool => $quiz->learningSubject()->slug === $data['subject']);
        }
        $mock = isset($data['mode']);
        $sections = $mock ? [['title' => 'Build a timed mock test', 'description' => 'Sign in to choose subjects, create a mixed practice test and save your results.', 'links' => [['label' => 'Create a mock test in My learning', 'url' => route('learning')]]]] : [];
        $sections[] = ['title' => 'Practice by subject', 'links' => $navigation->subjects()->filter(fn ($subject): bool => $navigation->quizzes()->contains(fn (Quiz $quiz): bool => ! $quiz->isQuestionAnswer() && $quiz->learningSubject()->id === $subject->id))->map(fn ($subject): array => ['label' => $subject->name, 'url' => route('practice.index', ['subject' => $subject->slug])])->values()->all()];
        $sections[] = ['title' => 'Available topic-wise sets', 'links' => $this->quizLinks($quizzes->take(30))];

        return $this->page($mock ? 'Mock tests' : 'Topic-wise practice sets', 'Choose a published question set or build a timed mock test. Daily, weekly and monthly MCQs remain at their existing URLs.', $sections);
    }

    public function currentAffairs(Request $request, WebsiteNavigation $navigation): View
    {
        $data = $request->validate(['period' => 'sometimes|in:daily,weekly,monthly']);
        $period = $data['period'] ?? null;
        $sets = $navigation->quizzes()->filter(fn (Quiz $quiz): bool => $quiz->isCurrentAffairs());
        if ($period) {
            $start = now('Asia/Kolkata')->startOfDay();
            if ($period === 'weekly') {
                $start->startOfWeek();
            } elseif ($period === 'monthly') {
                $start->startOfMonth();
            }
            $sets = $sets->filter(fn (Quiz $quiz): bool => $quiz->quiz_date !== null && $quiz->quiz_date->toDateString() >= $start->toDateString() && $quiz->quiz_date->toDateString() <= now('Asia/Kolkata')->toDateString());
        }

        return $this->page($period ? ucfirst($period).' current affairs' : 'Current Affairs', 'Read published current-affairs question sets. Daily means today, weekly means this calendar week and monthly means this calendar month in IST. Undated sets appear in the full archive.', [['title' => 'Published capsules and question sets', 'links' => $this->quizLinks($sets->take(30))], ['title' => 'Full archive', 'links' => [['label' => 'All published current affairs sets', 'url' => route('subject', 'current-affairs')]]]]);
    }

    public function studyTips(WebsiteNavigation $navigation): View
    {
        return $this->page('Study Tips', 'Published articles about revision, study planning and preparation.', [['title' => 'Preparation and learning', 'links' => $this->postLinks($navigation->studyTips()->take(30))]]);
    }
}
