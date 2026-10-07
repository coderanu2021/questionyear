<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentAffairsTest extends TestCase
{
    use RefreshDatabase;

    public function test_standalone_current_affairs_quiz_can_be_created_played_and_hidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $state = $this->actingAs($admin)->get(route('admin'))->viewData('state');
        $state['tests'][] = ['id' => 1, 'ch' => null, 'current_affairs' => true, 'title' => 'Daily news quiz', 'quiz_date' => '2026-10-07', 'dur' => 10, 'pass' => 40, 'qs' => [['q' => 'What is 2 + 2?', 'o' => ['1', '2', '3', '4'], 'c' => 3]]];
        $state = $this->putJson(route('admin.save'), $state)->assertOk()->assertJsonPath('tests.0.ch', null)->assertJsonPath('tests.0.current_affairs', true)->json();
        $quiz = Quiz::findOrFail(1);
        $this->assertDatabaseCount('chapters', 0);
        $this->assertSame('current-affairs', $quiz->learningSubject()->slug);
        $this->get($quiz->publicUrl())->assertOk();
        $this->get(route('subject', 'current-affairs'))->assertOk()->assertViewHas('quizIds', ['current-affairs:0' => 1]);
        $this->get(route('sitemap'))->assertOk()->assertSee($quiz->publicUrl(), false);
        $this->get(route('admin.seo.index'))->assertOk();
        $this->postJson('/quizzes/1/answer', ['question' => 0, 'answer' => 3])->assertOk()->assertJsonPath('correct', 3);
        $this->postJson('/quizzes/1/attempts', ['answers' => [3], 'seconds' => 10])->assertOk()->assertJsonPath('score', 1);
        $this->assertDatabaseHas('learning_questions', ['subject_id' => $quiz->subject_id]);
        $state['tests'][0]['status'] = 'draft';
        $this->putJson(route('admin.save'), $state)->assertOk();
        $this->get($quiz->publicUrl())->assertNotFound();
        $this->postJson('/quizzes/1/answer', ['question' => 0, 'answer' => 3])->assertNotFound();
        $this->get(route('sitemap'))->assertDontSee($quiz->publicUrl(), false);
    }

    public function test_quiz_date_can_be_saved_edited_cleared_and_validated(): void
    {
        $subject = Subject::factory()->create(['name' => 'Current Affairs', 'slug' => 'current-affairs']);
        $chapter = Chapter::factory()->for($subject)->create();
        $quiz = Quiz::factory()->for($chapter)->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $state = $this->actingAs($admin)->get(route('admin'))->viewData('state');
        $state['tests'][0]['quiz_date'] = '2026-10-07';
        $state = $this->putJson(route('admin.save'), $state)->assertOk()->assertJsonPath('tests.0.quiz_date', '2026-10-07')->json();
        $this->assertSame('2026-10-07', $quiz->fresh()->quiz_date->toDateString());
        $curriculum = $this->get($quiz->publicUrl())->assertOk()->viewData('curriculum');
        $this->assertSame('07 Oct 2026', $curriculum['tests']['current-affairs:0'][0]['date_label']);
        $state['tests'][0]['quiz_date'] = '2026-02-30';
        $this->putJson(route('admin.save'), $state)->assertUnprocessable()->assertJsonValidationErrors('tests.0.quiz_date');
        $state['tests'][0]['quiz_date'] = '2026-10-08';
        $state = $this->putJson(route('admin.save'), $state)->assertOk()->assertJsonPath('tests.0.quiz_date', '2026-10-08')->json();
        unset($state['tests'][0]['quiz_date']);
        $state = $this->putJson(route('admin.save'), $state)->assertOk()->assertJsonPath('tests.0.quiz_date', '2026-10-08')->json();
        $state['tests'][0]['quiz_date'] = null;
        $this->putJson(route('admin.save'), $state)->assertOk()->assertJsonPath('tests.0.quiz_date', null);
        $this->assertNull($quiz->fresh()->quiz_date);
    }

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
        $this->assertCount(1, $curriculum['tests']['current-affairs:0']);
        $this->assertCount(1, $curriculum['tests']['current-affairs:1']);
        $this->assertArrayNotHasKey('current-affairs:2', $curriculum['tests']);
        $this->get(route('learn', ['subject' => $subject->slug, 'chapter' => 0]))->assertNotFound();
        foreach ($quizzes as $quiz) {
            $response = $this->get($quiz->publicUrl())->assertOk();
            $this->assertContains($quiz->id, $response->viewData('quizIds'));
        }
        $regularChapter = Chapter::factory()->create();
        $this->get($regularChapter->readingUrl(0))->assertOk();
    }
}
