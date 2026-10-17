<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chapter_content_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->uuid('batch_id')->index();
            $table->string('status')->default('draft')->index();
            $table->string('generation_state')->default('queued');
            $table->unsignedInteger('version')->default(1);
            $table->json('brief');
            $table->json('payload');
            $table->json('checks')->nullable();
            $table->json('original');
            $table->string('original_hash', 64);
            $table->string('verified_hash', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('review_note')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
        Schema::table('chapters', function (Blueprint $table): void {
            $table->json('content_evidence')->nullable();
            $table->timestamp('content_verified_at')->nullable();
            $table->timestamp('content_review_due_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table): void {
            $table->dropColumn(['content_evidence', 'content_verified_at', 'content_review_due_at']);
        });
        Schema::dropIfExists('chapter_content_revisions');
    }
};
