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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('organization')->nullable();
            $table->text('summary')->nullable();
            $table->unsignedInteger('vacancies')->nullable();
            $table->string('state')->nullable();
            $table->string('qualification_level')->nullable();
            $table->string('advertisement_no')->nullable();
            $table->string('fee')->nullable();
            $table->string('age_limit')->nullable();
            $table->date('start_date')->nullable();
            $table->date('last_date')->nullable();
            $table->string('pdf_url', 512)->nullable();
            $table->string('official_url', 512)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('source_checked_at')->nullable();
            $table->foreignId('raw_notice_id')->nullable()->constrained('raw_notices')->nullOnDelete();
            $table->index(['status', 'last_date']);
            $table->index(['state', 'qualification_level']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
