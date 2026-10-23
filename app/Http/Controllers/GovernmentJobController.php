<?php

namespace App\Http\Controllers;

use App\Models\JobNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GovernmentJobController extends Controller
{
    public function index(Request $request, string $category = 'all'): View
    {
        abort_unless(isset(ExamNotificationController::CATEGORIES[$category]), 404);

        $query = JobNotification::query()
            ->whereIn('status', ['published', 'closed'])
            ->whereNotNull('official_url');

        if ($category === 'admit-cards') {
            $query->where('notice_type', 'admit-card');
        } elseif ($category === 'results') {
            $query->where('notice_type', 'result');
        } elseif (in_array($category, ['10th-pass', '12th-pass', 'graduate'], true)) {
            $query->where('qualification_level', $category);
        } elseif (in_array($category, ['ssc', 'railway', 'banking', 'upsc'], true)) {
            $organization = ['ssc' => 'Staff Selection Commission', 'railway' => 'Railway', 'banking' => 'Bank', 'upsc' => 'Union Public Service Commission'][$category];
            $query->where('organization', 'like', '%'.$organization.'%');
        } elseif ($category === 'upcoming') {
            $query->whereNotNull('start_date')->whereDate('start_date', '>', today());
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->query('q'));
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', '%'.$search.'%')
                    ->orWhere('organization', 'like', '%'.$search.'%')
                    ->orWhere('advertisement_no', 'like', '%'.$search.'%');
            });
        }
        if ($request->filled('state')) {
            $query->where('state', $request->query('state'));
        }
        if ($request->filled('qualification')) {
            $query->where('qualification_level', $request->query('qualification'));
        }
        if ($request->filled('department')) {
            $query->where('organization', $request->query('department'));
        }

        $notifications = $query->orderByRaw('CASE WHEN last_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('last_date')->orderByDesc('source_checked_at')->paginate(15)->withQueryString();

        return view('website.government-jobs-live', [
            'title' => ExamNotificationController::CATEGORIES[$category],
            'category' => $category,
            'categories' => ExamNotificationController::CATEGORIES,
            'notifications' => $notifications,
            'states' => JobNotification::query()->whereIn('status', ['published', 'closed'])->whereNotNull('state')->distinct()->orderBy('state')->pluck('state'),
            'qualifications' => ['10th-pass' => '10th pass', '12th-pass' => '12th pass', 'graduate' => 'Graduate', 'postgraduate' => 'Postgraduate', 'diploma' => 'Diploma', 'other' => 'Other'],
            'departments' => JobNotification::query()->whereIn('status', ['published', 'closed'])->whereNotNull('organization')->distinct()->orderBy('organization')->limit(30)->pluck('organization'),
        ]);
    }

    public function state(Request $request, string $state): View
    {
        $stateName = JobNotification::query()->whereIn('status', ['published', 'closed'])->whereNotNull('state')->get(['state'])->pluck('state')->first(fn (string $name): bool => Str::slug($name) === $state);
        abort_unless($stateName !== null, 404);
        $request->query->set('state', $stateName);

        return $this->index($request);
    }

    public function qualification(Request $request, string $level): View
    {
        abort_unless(in_array($level, ['10th-pass', '12th-pass', 'graduate', 'postgraduate', 'diploma', 'other'], true), 404);
        $request->query->set('qualification', $level);

        return $this->index($request, $level);
    }

    public function department(Request $request, string $department): View
    {
        $organization = JobNotification::query()->whereIn('status', ['published', 'closed'])->whereNotNull('organization')->get(['organization'])->pluck('organization')->first(fn (string $name): bool => Str::slug($name) === $department);
        abort_unless($organization !== null, 404);
        $request->query->set('department', $organization);

        return $this->index($request);
    }

    public function show(string $year, string $slug): View
    {
        $notification = JobNotification::query()
            ->where('slug', $slug)
            ->whereYear('created_at', $year)
            ->whereIn('status', ['published', 'closed'])
            ->whereNotNull('official_url')
            ->firstOrFail();

        return view('website.government-job-detail', ['notification' => $notification, 'title' => $notification->title]);
    }
}
