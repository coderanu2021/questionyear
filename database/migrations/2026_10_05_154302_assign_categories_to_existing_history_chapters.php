<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $categories = [
            'Ancient History' => ['Prehistoric Period', 'Indus Valley Civilization', 'Vedic Age', 'Mauryan and Gupta Empires'],
            'Medieval History' => ['Delhi Sultanate', 'Mughal Empire'],
            'Modern History' => ['Revolt of 1857', 'Freedom Struggle', 'Post-Independence India'],
        ];
        $subjectIds = DB::table('subjects')->where('slug', 'history')->pluck('id');
        foreach ($categories as $category => $titles) {
            DB::table('chapters')->whereIn('subject_id', $subjectIds)->whereIn('title', $titles)
                ->where(fn ($query) => $query->whereNull('category')->orWhere('category', ''))
                ->update(['category' => $category]);
        }
    }

    /**
     * Preserve category assignments, including subsequent administrator edits.
     */
    public function down(): void {}
};
