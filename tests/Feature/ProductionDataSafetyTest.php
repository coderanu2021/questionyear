<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionDataSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_destructive_commands_are_blocked_in_production_even_with_force(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $this->app->instance('env', 'production');
        $this->app->getProvider(AppServiceProvider::class)->boot();
        try {
            foreach (['db:wipe', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback'] as $command) {
                $this->artisan($command, ['--force' => true, '--no-interaction' => true])->assertFailed();
                $this->assertDatabaseHas('users', ['id' => $user->id]);
                $this->assertDatabaseHas('quizzes', ['id' => $quiz->id]);
            }
        } finally {
            $this->app->instance('env', 'testing');
            DB::prohibitDestructiveCommands(false);
        }
    }

    public function test_practice_migration_preserves_existing_content_and_daily_attempts(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();
        $migration = require database_path('migrations/2026_10_06_120000_create_practice_question_bank.php');
        $migration->down();
        $attempt = ['user_id' => $user->id, 'participant' => 'user-'.$user->id, 'quiz_date' => '2026-10-05', 'set_number' => 1, 'answers' => '[3]', 'questions' => json_encode($quiz->questions), 'score' => 1, 'total' => 1];
        $attemptId = DB::table('daily_quiz_attempts')->insertGetId($attempt);
        $migration->up();
        $this->assertDatabaseHas('daily_quiz_attempts', ['id' => $attemptId, ...$attempt, 'period' => 'daily']);
        $this->assertSame($quiz->questions, $quiz->fresh()->questions);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('chapters', ['id' => $quiz->chapter_id]);
    }
}
