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
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('featured_image_url')->nullable();
            $table->string('featured_image_alt')->nullable();
            $table->string('author_name')->nullable();
            $table->string('official_source_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn(['featured_image_url', 'featured_image_alt', 'author_name', 'official_source_url']);
        });
    }
};
