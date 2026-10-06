<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('fingerprint', 64)->unique();
            $table->json('question');
            $table->string('source');
            $table->timestamps();
        });
        Schema::create('practice_sets', function (Blueprint $table): void {
            $table->id();
            $table->string('period', 10);
            $table->date('starts_on');
            $table->json('questions');
            $table->timestamps();
            $table->unique(['period', 'starts_on']);
        });
        Schema::table('daily_quiz_attempts', function (Blueprint $table): void {
            $table->dropUnique(['participant', 'quiz_date', 'set_number']);
            $table->string('period', 10)->default('daily');
            $table->unique(['participant', 'quiz_date', 'set_number', 'period'], 'practice_attempt_unique');
        });
    }

    public function down(): void
    {
        Schema::table('daily_quiz_attempts', function (Blueprint $table): void {
            $table->dropUnique('practice_attempt_unique');
        });
        DB::table('daily_quiz_attempts')->where('period', '!=', 'daily')->delete();
        Schema::table('daily_quiz_attempts', function (Blueprint $table): void {
            $table->dropColumn('period');
            $table->unique(['participant', 'quiz_date', 'set_number']);
        });
        Schema::dropIfExists('practice_sets');
        Schema::dropIfExists('practice_questions');
    }
};
