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
        Schema::create('raw_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('job_sources')->cascadeOnDelete();
            $table->string('url', 512);
            $table->text('title');
            $table->char('hash', 64);
            $table->longText('raw_text');
            $table->timestamp('fetched_at');
            $table->unique(['source_id', 'hash']);
            $table->index(['source_id', 'fetched_at']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_notices');
    }
};
