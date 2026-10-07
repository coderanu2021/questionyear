<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->unsignedBigInteger('chapter_id')->nullable()->change();
            $table->foreignId('subject_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status')->default('published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('quizzes')->whereNull('chapter_id')->exists()) {
            throw new RuntimeException('Cannot roll back while standalone quizzes exist. Their subject links must be preserved.');
        }
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropColumn(['subject_id', 'status']);
            $table->unsignedBigInteger('chapter_id')->nullable(false)->change();
        });
    }
};
