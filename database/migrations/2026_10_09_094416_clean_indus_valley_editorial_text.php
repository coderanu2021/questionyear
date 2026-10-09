<?php

use App\IndusValleyContent;
use App\Models\Chapter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Chapter::whereHas('subject', fn ($query) => $query->where('slug', 'history'))
            ->where('title', 'Indus Valley Civilization')->each(function (Chapter $chapter): void {
                $cleaned = IndusValleyContent::clean($chapter->content ?? '');
                if ($cleaned !== $chapter->content && filled($chapter->content)) {
                    $chapter->update(['content' => $cleaned]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /** Editorial corrections are retained when application code is rolled back. */
    }
};
