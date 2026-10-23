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
        Schema::table('job_sources', function (Blueprint $table): void {
            $table->string('terms_url', 512)->nullable()->after('parser');
            $table->timestamp('terms_reviewed_at')->nullable()->after('terms_url');
            $table->boolean('terms_allows_automated_access')->default(false)->after('terms_reviewed_at');
            $table->index(['active', 'terms_allows_automated_access']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_sources', function (Blueprint $table): void {
            $table->dropIndex(['active', 'terms_allows_automated_access']);
            $table->dropColumn(['terms_url', 'terms_reviewed_at', 'terms_allows_automated_access']);
        });
    }
};
