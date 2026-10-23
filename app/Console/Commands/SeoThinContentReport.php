<?php

namespace App\Console\Commands;

use App\Models\Chapter;
use App\Models\Post;
use App\Models\Quiz;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('seo:thin-content')]
#[Description('Report published posts under 300 words and topics with fewer than 5 MCQs.')]
class SeoThinContentReport extends Command
{
    public function handle(): int
    {
        $posts = Post::query()
            ->whereIn('type', ['blog', 'news'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get()
            ->map(function (Post $post): array {
                $words = str_word_count(html_entity_decode(strip_tags($post->content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                return ['id' => $post->id, 'type' => $post->type, 'title' => $post->title, 'words' => $words];
            })
            ->filter(fn (array $post): bool => $post['words'] < 300)
            ->values();

        $topics = Chapter::query()
            ->with(['subject', 'quizzes'])
            ->where('status', 'published')
            ->get()
            ->map(function (Chapter $chapter): array {
                $questionCount = $chapter->quizzes
                    ->filter(fn (Quiz $quiz): bool => $quiz->isPublished() && ! $quiz->isQuestionAnswer())
                    ->sum(fn (Quiz $quiz): int => count($quiz->questions ?? []));

                return ['id' => $chapter->id, 'subject' => $chapter->subject->name, 'title' => $chapter->title, 'mcqs' => $questionCount];
            })
            ->filter(fn (array $topic): bool => $topic['mcqs'] < 5)
            ->values();

        $this->info('Published posts under 300 words: '.$posts->count());
        foreach ($posts as $post) {
            $this->line(sprintf('Post #%d [%s] %s — %d words', $post['id'], $post['type'], $post['title'], $post['words']));
        }

        $this->info('Published topics with fewer than 5 MCQs: '.$topics->count());
        foreach ($topics as $topic) {
            $this->line(sprintf('Topic #%d [%s] %s — %d MCQs', $topic['id'], $topic['subject'], $topic['title'], $topic['mcqs']));
        }

        return self::SUCCESS;
    }
}
