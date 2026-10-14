<?php

use App\Models\Subject;
use Database\Seeders\HistoryStudyGuideSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Subject::where('slug', 'history')->exists()) {
            (new HistoryStudyGuideSeeder)->run();
        }
    }

    /**
     * Retain simplified guides when application code is rolled back.
     */
    public function down(): void {}
};
