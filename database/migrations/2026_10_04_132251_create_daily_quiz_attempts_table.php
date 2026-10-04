<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('participant', 80);
            $table->date('quiz_date');
            $table->unsignedTinyInteger('set_number');
            $table->json('answers');
            $table->json('questions');
            $table->unsignedInteger('score');
            $table->unsignedInteger('total');
            $table->timestamps();
            $table->unique(['participant', 'quiz_date', 'set_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_quiz_attempts');
    }
};
