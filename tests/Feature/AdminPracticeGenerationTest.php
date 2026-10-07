<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminPracticeGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_generation_publishes_twenty_questions_and_scheduler_skips_api(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Subject::factory()->create();
        $questions = array_map(fn (int $index): array => ['q' => 'Question '.$index, 'o' => ['A', 'B', 'C', 'D'], 'c' => 0, 'explanation' => 'Explanation'], range(1, 20));
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($questions)]]]]]])]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/admin/practice')->assertOk()->assertSee('Pending generation')->assertSee('Generate Daily Quiz');
        $this->post(route('admin.practice.generate'), ['period' => 'daily'])->assertRedirect('/admin/practice')->assertSessionHasNoErrors();
        $snapshot = DB::table('practice_sets')->value('questions');
        $this->assertCount(20, json_decode($snapshot, true));
        $this->post(route('admin.practice.generate'), ['period' => 'daily'])->assertSessionHasNoErrors();
        $this->artisan('practice:generate daily')->assertSuccessful();
        $this->assertDatabaseCount('practice_sets', 1);
        $this->assertSame($snapshot, DB::table('practice_sets')->value('questions'));
        $this->get('/admin/practice')->assertOk()->assertSee('Already generated')->assertSee('20 / 20');
        Http::assertSentCount(1);
    }

    public function test_generation_requires_active_admin_and_valid_period(): void
    {
        Http::preventStrayRequests();
        $this->post(route('admin.practice.generate'), ['period' => 'daily'])->assertRedirect(route('login'));
        foreach ([['role' => 'student'], ['role' => 'admin', 'status' => 'blocked']] as $attributes) {
            $this->actingAs(User::factory()->create($attributes));
            $this->get('/admin/practice')->assertForbidden();
            $this->post(route('admin.practice.generate'), ['period' => 'daily'])->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post(route('admin.practice.generate'), ['period' => 'yearly'])->assertSessionHasErrors('period');
        Http::assertNothingSent();
    }

    public function test_api_failure_is_shown_without_publishing_a_quiz(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Subject::factory()->create();
        Http::fake(['*' => Http::response([], 429)]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post(route('admin.practice.generate'), ['period' => 'daily'])->assertRedirect('/admin/practice')->assertSessionHasErrors('generation');
        $this->assertDatabaseCount('practice_sets', 0);
    }
}
