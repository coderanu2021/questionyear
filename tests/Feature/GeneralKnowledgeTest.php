<?php

namespace Tests\Feature;

use App\LearningTracker;
use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralKnowledgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_gk_can_be_created_without_chapters_read_edited_and_unpublished(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $state = $this->actingAs($admin)->get(route('admin'))->viewData('state');
        $state['tests'][] = ['id' => 1, 'ch' => null, 'general_knowledge' => true, 'title' => 'GK Questions', 'qs' => [['q' => 'What is 2 + 2?', 'answer' => 'Four']]];
        $state = $this->putJson(route('admin.save'), $state)->assertOk()->assertJsonPath('tests.0.general_knowledge', true)->assertJsonPath('tests.0.ch', null)->json();
        $quiz = Quiz::findOrFail(1);
        $this->assertSame('general-knowledge', $quiz->learningSubject()->slug);
        $this->assertDatabaseCount('chapters', 0);
        $response = $this->get(route('subject', 'general-knowledge'))->assertOk()->assertSee('General Knowledge quizzes');
        $curriculum = $response->viewData('curriculum');
        $this->assertSame([], $curriculum['LN']);
        $this->assertSame([], $curriculum['chapterUrls']);
        $this->assertSame([], $curriculum['QB']['general-knowledge:0']);
        $this->get($quiz->publicUrl())->assertOk()->assertSee('GK Questions')->assertSee('What is 2 + 2?')->assertSee('Four');
        $this->get('/learn/general-knowledge/0')->assertNotFound();
        $this->postJson('/quizzes/1/answer', ['question' => 0, 'answer' => 3])->assertNotFound();
        $this->postJson('/quizzes/1/attempts', ['answers' => [3], 'seconds' => 10])->assertNotFound();
        $this->assertSame([], app(LearningTracker::class)->importBank());
        $this->get(route('sitemap'))->assertSee($quiz->publicUrl(), false);
        $this->get(route('admin.seo.index'))->assertOk();
        $state['tests'][0]['title'] = 'Updated GK Practice';
        $state = $this->putJson(route('admin.save'), $state)->assertOk()->assertJsonPath('tests.0.title', 'Updated GK Practice')->json();
        $quiz->refresh();
        $state['tests'][0]['status'] = 'draft';
        $this->putJson(route('admin.save'), $state)->assertOk();
        $this->get($quiz->publicUrl())->assertNotFound();
        $this->postJson('/quizzes/1/answer', ['question' => 0, 'answer' => 3])->assertNotFound();
        $this->get(route('sitemap'))->assertDontSee($quiz->publicUrl(), false);
    }

    public function test_gk_requires_an_answer_and_rejects_options_and_explanations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $state = $this->actingAs($admin)->get(route('admin'))->viewData('state');
        $state['tests'][] = ['id' => 1, 'ch' => null, 'general_knowledge' => true, 'title' => 'GK', 'dur' => 10, 'pass' => 50, 'qs' => [['q' => 'Question?']]];
        $this->putJson(route('admin.save'), $state)->assertUnprocessable()->assertJsonValidationErrors('tests.0.qs.0.answer');
        $state['tests'][0]['qs'][0] = ['q' => 'Question?', 'answer' => 'Answer', 'o' => ['A', 'B', 'C', 'D'], 'c' => 0, 'explanation' => 'Explanation'];
        $this->putJson(route('admin.save'), $state)->assertUnprocessable()->assertJsonValidationErrors(['tests.0.qs.0.o', 'tests.0.qs.0.c', 'tests.0.qs.0.explanation']);
        $state['tests'][0]['current_affairs'] = true;
        $this->putJson(route('admin.save'), $state)->assertUnprocessable()->assertJsonValidationErrors('tests.0.general_knowledge');
        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_legacy_gk_quizzes_keep_links_but_show_without_chapters(): void
    {
        $subject = Subject::factory()->create(['name' => 'General Knowledge', 'slug' => 'general-knowledge']);
        $chapter = Chapter::factory()->for($subject)->create();
        $quizzes = Quiz::factory()->count(2)->for($chapter)->create();
        $response = $this->get(route('subject', $subject->slug))->assertOk();
        $this->assertCount(2, $response->viewData('quizIds'));
        $this->assertSame([], $response->viewData('curriculum')['chapterUrls']);
        foreach ($quizzes as $quiz) {
            $this->get($quiz->publicUrl())->assertOk();
            $this->assertSame($chapter->id, $quiz->fresh()->chapter_id);
        }
        $this->get(route('sitemap'))->assertDontSee($chapter->readingUrl(0), false);
    }
}
