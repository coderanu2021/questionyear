<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuestionPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_create_and_edit_question_pages_and_save_questions(): void
    {
        $quiz = Quiz::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('admin.tests.create'))->assertOk()->assertViewHas('testPage', ['id' => null]);
        $response = $this->get(route('admin.tests.edit', $quiz));
        $response->assertOk()->assertViewHas('testPage', ['id' => $quiz->id])->assertSee('window.TEST_PAGE', false);
        $state = $response->viewData('state');
        $state['tests'][0]['qs'][] = ['q' => 'What is 3 + 3?', 'o' => ['3', '4', '5', '6'], 'c' => 3, 'explanation' => 'Three plus three is six.'];
        $this->putJson(route('admin.save'), $state)->assertOk()->assertJsonCount(2, 'tests.0.qs');
        $this->assertCount(2, $quiz->fresh()->questions);
        $this->get('/admin/tests/999999/edit')->assertNotFound();
    }

    public function test_question_pages_require_an_active_admin(): void
    {
        $quiz = Quiz::factory()->create();
        foreach ([route('admin.tests.create'), route('admin.tests.edit', $quiz)] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        foreach ([['role' => 'student'], ['role' => 'admin', 'status' => 'blocked']] as $attributes) {
            $this->actingAs(User::factory()->create($attributes));
            $this->get(route('admin.tests.create'))->assertForbidden();
            $this->get(route('admin.tests.edit', $quiz))->assertForbidden();
        }
    }
}
