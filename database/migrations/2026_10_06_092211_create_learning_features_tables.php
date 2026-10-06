<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_questions', function (Blueprint $table): void {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->json('question');
            $table->text('explanation_hi')->nullable();
            $table->timestamps();
        });
        Schema::create('learning_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('learning_questions')->cascadeOnDelete();
            $table->boolean('bookmarked')->default(false);
            $table->boolean('wrong')->default(false);
            $table->smallInteger('last_answer')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'question_id']);
        });
        Schema::create('learning_activity', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('learning_questions')->cascadeOnDelete();
            $table->date('activity_date');
            $table->boolean('correct');
            $table->timestamps();
            $table->unique(['user_id', 'question_id', 'activity_date']);
        });
        Schema::create('learning_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('daily_target')->default(10);
            $table->string('language', 2)->default('en');
            $table->string('exam', 20)->default('general');
            $table->boolean('history_imported')->default(false);
            $table->timestamps();
        });
        Schema::create('learning_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 20);
            $table->string('title', 120);
            $table->json('questions');
            $table->unsignedInteger('duration');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
        Schema::create('learning_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('session_id');
            $table->foreign('session_id')->references('id')->on('learning_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->json('answers')->nullable();
            $table->unsignedInteger('score')->nullable();
            $table->timestamps();
            $table->unique(['session_id', 'user_id']);
        });
        Schema::create('question_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('question_id')->constrained('learning_questions')->cascadeOnDelete();
            $table->text('reason');
            $table->string('source', 100);
            $table->string('status', 20)->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['question_reports', 'learning_runs', 'learning_sessions', 'learning_preferences', 'learning_activity', 'learning_progress', 'learning_questions'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
