<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ExamNotificationController extends Controller
{
    /** @var array<string, array{title: string, summary: string, status: string}> */
    public const NOTICES = [
        'upsc' => ['title' => 'UPSC Civil Services 2027', 'summary' => 'Calendar dates, prior-year eligibility and attempt rules.', 'status' => '2027 calendar published'],
        'rrb-ntpc-graduate-cen-06-2026' => ['title' => 'RRB NTPC Graduate — CEN 06/2026', 'summary' => 'Application dates, degree rules and the 1 January 2027 age cut-off.', 'status' => 'Published 2026 notice'],
        'rrb-ntpc-undergraduate-cen-07-2025' => ['title' => 'RRB NTPC Undergraduate — CEN 07/2025', 'summary' => 'Class 12 entry rules from the prior notice. Fresh 2027 terms are not verified.', 'status' => 'Prior-year reference'],
        'rrb-group-d-cen-09-2025' => ['title' => 'RRB Group D / Level 1 — CEN 09/2025', 'summary' => 'Prior-notice education, age and fitness rules for planning your next step.', 'status' => 'Prior-year reference'],
    ];

    public function index(?string $exam = null): View
    {
        abort_unless($exam === null || $exam === 'railways' || isset(self::NOTICES[$exam]), 404);

        return view('website.notifications', [
            'exam' => $exam,
            'notices' => $exam === 'railways' ? array_diff_key(self::NOTICES, ['upsc' => true]) : self::NOTICES,
            'title' => match ($exam) {
                'upsc' => 'UPSC CSE 2027: dates and eligibility',
                'railways' => 'Railway exams for 2027: notices and eligibility',
                default => self::NOTICES[$exam]['title'] ?? 'Upcoming exams 2027: UPSC and Railway notices',
            },
            'description' => match ($exam) {
                'upsc' => 'Check UPSC CSE 2027 calendar dates, prior-year age and degree rules, attempt limits and official sources. Final 2027 rules need the full notice.',
                'railways' => 'Plan for Railway exams with NTPC and Group D eligibility notes. See CEN 06/2026 dates, its 2027 age cut-off and clearly marked prior-year rules.',
                default => self::NOTICES[$exam]['summary'] ?? 'Find UPSC and Railway exam updates for 2027. Compare confirmed dates, eligibility references and pending notices, with links to official sources.',
            },
        ]);
    }
}
