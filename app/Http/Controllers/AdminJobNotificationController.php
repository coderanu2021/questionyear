<?php

namespace App\Http\Controllers;

use App\Models\JobNotification;
use App\Services\JobAlerts\GovernmentJobAlertDispatcher;
use App\Services\JobSources\OfficialSourceUrlPolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminJobNotificationController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin' && $request->user()?->status === 'active', 403);
    }

    public function update(Request $request, JobNotification $jobNotification, OfficialSourceUrlPolicy $urlPolicy): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless($jobNotification->status === 'pending', 409);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'vacancies' => ['nullable', 'integer', 'between:0,1000000'],
            'state' => ['nullable', 'string', 'max:100'],
            'qualification_level' => ['nullable', 'string', 'in:10th-pass,12th-pass,graduate,postgraduate,diploma,other'],
            'qualification_details' => ['nullable', 'string', 'max:3000'],
            'advertisement_no' => ['nullable', 'string', 'max:100'],
            'fee' => ['nullable', 'string', 'max:500'],
            'age_limit' => ['nullable', 'string', 'max:500'],
            'start_date' => ['nullable', 'date'],
            'last_date' => ['nullable', 'date'],
            'pdf_url' => ['nullable', 'url', 'max:512'],
            'application_url' => ['nullable', 'url', 'max:512'],
            'official_url' => ['required', 'url', 'max:512'],
            'notice_type' => ['nullable', 'string', 'in:job,admit-card,result,deadline,update'],
            'confidence' => ['nullable', 'numeric', 'between:0,1'],
        ]);

        foreach (['official_url', 'pdf_url', 'application_url'] as $urlField) {
            if (($data[$urlField] ?? null) !== null && ! $urlPolicy->allows($data[$urlField], $jobNotification->official_url)) {
                throw ValidationException::withMessages([$urlField => 'Use a link on an official government domain.']);
            }
        }

        $jobNotification->fill($data)->save();

        return redirect()->route('admin.job-notices.index')->with('status', 'Government job notice updated.');
    }

    public function approve(Request $request, JobNotification $jobNotification, OfficialSourceUrlPolicy $urlPolicy): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $this->assertPending($jobNotification);
        $this->assertOfficialSource($jobNotification, $urlPolicy);
        $this->publish([$jobNotification]);

        return redirect()->route('admin.job-notices.index')->with('status', 'Government job notice published.');
    }

    public function reject(Request $request, JobNotification $jobNotification): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $this->assertPending($jobNotification);
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:1000']]);

        $jobNotification->forceFill([
            'review_decision' => 'rejected',
            'reviewed_at' => now(),
            'review_note' => $data['review_note'] ?? null,
        ])->save();

        return redirect()->route('admin.job-notices.index')->with('status', 'Government job notice rejected and kept out of the public listing.');
    }

    public function restore(Request $request, JobNotification $jobNotification): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless($jobNotification->status === 'pending' && $jobNotification->review_decision === 'rejected', 409);

        $jobNotification->forceFill(['review_decision' => null, 'reviewed_at' => null, 'review_note' => null])->save();

        return redirect()->route('admin.job-notices.index')->with('status', 'Rejected notice returned to the pending review list.');
    }

    public function bulkPublish(Request $request, OfficialSourceUrlPolicy $urlPolicy): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'notification_ids' => ['required', 'array', 'min:1', 'max:100'],
            'notification_ids.*' => ['required', 'integer', 'distinct', 'exists:notifications,id'],
        ]);

        $jobNotifications = JobNotification::query()
            ->whereIn('id', $data['notification_ids'])
            ->where('status', 'pending')
            ->whereNull('review_decision')
            ->get();
        abort_unless($jobNotifications->count() === count($data['notification_ids']), 409, 'Some selected notices are no longer pending review.');

        foreach ($jobNotifications as $jobNotification) {
            $this->assertOfficialSource($jobNotification, $urlPolicy);
        }

        $this->publish($jobNotifications);

        return redirect()->route('admin.job-notices.index')->with('status', $jobNotifications->count().' government job notices published.');
    }

    private function assertPending(JobNotification $jobNotification): void
    {
        abort_unless($jobNotification->status === 'pending' && $jobNotification->review_decision === null, 409, 'This notice is no longer pending review.');
    }

    private function assertOfficialSource(JobNotification $jobNotification, OfficialSourceUrlPolicy $urlPolicy): void
    {
        if (! $jobNotification->official_url || ! $urlPolicy->allows($jobNotification->official_url, $jobNotification->official_url)) {
            throw ValidationException::withMessages(['official_url' => 'A valid official government source link is required before publishing.']);
        }

        if (! $jobNotification->rawNotice?->source?->terms_allows_automated_access) {
            throw ValidationException::withMessages(['official_url' => "Review and approve this source's official website terms before publishing notices fetched from it."]);
        }
    }

    /** @param Collection<int, JobNotification> $jobNotifications */
    private function publish(Collection $jobNotifications): void
    {
        DB::transaction(function () use ($jobNotifications): void {
            foreach ($jobNotifications as $jobNotification) {
                $jobNotification->forceFill([
                    'status' => 'published',
                    'review_decision' => 'approved',
                    'reviewed_at' => now(),
                    'review_note' => null,
                ])->save();
                app(GovernmentJobAlertDispatcher::class)->dispatch($jobNotification);
            }
        });
    }
}
