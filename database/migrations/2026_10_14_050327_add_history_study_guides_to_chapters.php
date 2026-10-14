<?php

use App\Models\Subject;
use Database\Seeders\HistoryStudyGuideSeeder;
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
        if (! Schema::hasColumn('chapters', 'study_guide')) {
            Schema::table('chapters', function (Blueprint $table): void {
                $table->json('study_guide')->nullable();
            });
        }
        if (Subject::where('slug', 'history')->exists()) {
            (new HistoryStudyGuideSeeder)->run();
        }
    }

    /**
     * Retain learning content and attempts when application code is rolled back.
     */
    public function down(): void {}
};
