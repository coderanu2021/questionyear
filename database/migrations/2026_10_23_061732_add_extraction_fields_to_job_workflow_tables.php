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
        Schema::table('job_sources', function (Blueprint $table) {
            $table->boolean('trusted')->default(false);
        });
        DB::table('job_sources')->whereIn('url', [
            'https://ssc.gov.in/',
            'https://www.upsc.gov.in/',
            'https://rrb.indianrailways.gov.in/',
            'https://www.ibps.in/',
            'https://www.indiapost.gov.in/',
            'https://upsssc.gov.in/',
            'https://uppsc.up.nic.in/',
            'https://bpsc.bihar.gov.in/',
            'https://rpsc.rajasthan.gov.in/',
            'https://dsssb.delhi.gov.in/',
        ])->update(['trusted' => true]);

        Schema::table('raw_notices', function (Blueprint $table) {
            $table->string('extraction_status', 20)->default('pending')->index();
            $table->unsignedTinyInteger('extraction_attempts')->default(0);
            $table->timestamp('extracted_at')->nullable();
            $table->text('last_extraction_error')->nullable();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('notice_type', 20)->nullable()->index();
            $table->text('qualification_details')->nullable();
            $table->string('application_url', 512)->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['notice_type', 'qualification_details', 'application_url', 'confidence']);
        });

        Schema::table('raw_notices', function (Blueprint $table) {
            $table->dropIndex(['extraction_status']);
            $table->dropColumn(['extraction_status', 'extraction_attempts', 'extracted_at', 'last_extraction_error']);
        });

        Schema::table('job_sources', function (Blueprint $table) {
            $table->dropColumn('trusted');
        });
    }
};
