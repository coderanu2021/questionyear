<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ExamNotificationController extends Controller
{
    public function index(?string $exam = null): View
    {
        abort_unless($exam === null || in_array($exam, ['upsc', 'railways'], true), 404);

        return view('website.notifications', [
            'exam' => $exam,
            'title' => match ($exam) {
                'upsc' => 'UPSC CSE 2027: dates and eligibility',
                'railways' => 'Railway exams for 2027: notices and eligibility',
                default => 'Upcoming exams 2027: UPSC and Railway notices',
            },
            'description' => match ($exam) {
                'upsc' => 'Check UPSC CSE 2027 calendar dates, prior-year age and degree rules, attempt limits and official sources. Final 2027 rules need the full notice.',
                'railways' => 'Plan for Railway exams with NTPC and Group D eligibility notes. See CEN 06/2026 dates, its 2027 age cut-off and clearly marked prior-year rules.',
                default => 'Find UPSC and Railway exam updates for 2027. Compare confirmed dates, eligibility references and pending notices, with links to official sources.',
            },
        ]);
    }
}
