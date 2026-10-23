<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Post;
use App\Models\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoThinContentReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_lists_only_published_thin_posts_and_topics_without_modifying_content(): void
    {
        $thinPost = Post::factory()->published()->create(['content' => '<p>'.str_repeat('word ', 299).'</p>']);
        Post::factory()->published()->create(['content' => '<p>'.str_repeat('word ', 300).'</p>']);
        Post::factory()->create(['status' => 'draft', 'published_at' => null, 'content' => 'Draft body']);

        $chapter = Chapter::factory()->create(['title' => 'Thin topic']);
        $quiz = Quiz::factory()->for($chapter)->create(['questions' => array_fill(0, 4, ['q' => 'Question', 'o' => ['A', 'B', 'C', 'D'], 'c' => 0])]);
        $adequateTopic = Chapter::factory()->create(['title' => 'Adequate topic']);
        Quiz::factory()->for($adequateTopic)->create(['questions' => array_fill(0, 5, ['q' => 'Question', 'o' => ['A', 'B', 'C', 'D'], 'c' => 0])]);

        $this->artisan('seo:thin-content')
            ->expectsOutputToContain('Published posts under 300 words: 1')
            ->expectsOutputToContain('Post #'.$thinPost->id)
            ->expectsOutputToContain('Published topics with fewer than 5 MCQs:')
            ->expectsOutputToContain('Topic #'.$chapter->id)
            ->doesntExpectOutputToContain('Topic #'.$adequateTopic->id)
            ->assertExitCode(0);

        $this->assertSame('<p>'.str_repeat('word ', 299).'</p>', $thinPost->fresh()->content);
        $this->assertCount(4, $quiz->fresh()->questions);
    }
}
