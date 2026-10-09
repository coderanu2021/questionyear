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
        Schema::table('practice_sets', function (Blueprint $table): void {
            $table->unsignedInteger('set_number')->default(1);
            $table->dropUnique(['period', 'starts_on']);
            $table->unique(['period', 'starts_on', 'set_number'], 'practice_set_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('practice_sets')->select('period', 'starts_on')->groupBy('period', 'starts_on')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Rollback would remove support for archived quiz sets. Keep this migration while multiple sets exist.');
        }
        Schema::table('practice_sets', function (Blueprint $table): void {
            $table->dropUnique('practice_set_number_unique');
            $table->dropColumn('set_number');
            $table->unique(['period', 'starts_on']);
        });
    }
};
