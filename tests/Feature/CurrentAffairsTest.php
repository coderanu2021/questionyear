<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentAffairsTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_affairs_exposes_quizzes_without_reading_notes(): void
    {
        $subject = Subject::factory()->create(['name' => 'Current Affairs', 'slug' => 'current-affairs']);
        $chapter = Chapter::factory()->for($subject)->create();
        $quizzes = Quiz::factory()->count(2)->for($chapter)->create();
        $draft = Chapter::factory()->for($subject)->create(['status' => 'draft']);
        Quiz::factory()->for($draft)->create();
        $response = $this->get(route('subject', $subject->slug))->assertOk()->assertSee('Current Affairs quizzes')->assertSee('>Quizzes</h2>', false);
        $curriculum = $response->viewData('curriculum');
        $this->assertArrayNotHasKey('current-affairs:0', $curriculum['LN']);
        $this->assertCount(2, $curriculum['tests']['current-affairs:0']);
        $this->assertArrayNotHasKey('current-affairs:1', $curriculum['tests']);
        $this->get(route('learn', ['subject' => $subject->slug, 'chapter' => 0]))->assertNotFound();
        foreach ($quizzes as $quiz) {
            $this->get($quiz->publicUrl())->assertOk()->assertViewHas('quizIds', ['current-affairs:0' => $quiz->id]);
        }
        $regularChapter = Chapter::factory()->create();
        $this->get($regularChapter->readingUrl(0))->assertOk();
    }
}
