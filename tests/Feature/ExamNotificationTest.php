<?php

namespace Tests\Feature;

use App\Http\Controllers\ExamNotificationController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_undergraduate_2026_guide_shows_current_recruitment_details(): void
    {
        $slug = 'rrb-ntpc-undergraduate-cen-07-2026';

        $this->get(route('notifications.show', ['exam' => $slug]))->assertOk()
            ->assertSee('CEN 07/2026')->assertSee('1,688 (provisional)')
            ->assertSee('14 October 2026')->assertSee('15 October 2026')
            ->assertSee('13 November 2026, 11:59 PM')->assertSee('15 November 2026')
            ->assertSee('18–30 years as of 1 January 2027')->assertSee('marks requirements and exemptions')
            ->assertSee('26 September 2026')->assertSee('16 October 2026')
            ->assertSee('third-party-hosted copy')->assertSee('₹21,700')->assertSee('₹19,900')
            ->assertDontSee('Fresh 2027 CEN not verified here');

        foreach (['all', 'railway', '12th-pass'] as $category) {
            $this->get(route('jobs.category', ['category' => $category]))->assertOk()
                ->assertSee(route('notifications.show', ['exam' => $slug]), false)
                ->assertSee('1,688 (provisional)')->assertSee('13 November 2026, 11:59 PM');
        }

        $this->get(route('notifications.show', ['exam' => 'railways']))->assertOk()
            ->assertSee(route('notifications.show', ['exam' => $slug]), false);
        $this->get(route('sitemap'))->assertOk()
            ->assertSee(route('notifications.show', ['exam' => $slug]), false);
    }

    public function test_every_notice_has_specific_salary_guidance_and_faqs(): void
    {
        $expectedQuestions = [
            'upsc' => 'What is UPSC?',
            'rrb-ntpc-graduate-cen-06-2026' => 'What is the RRB NTPC Graduate salary?',
            'rrb-ntpc-undergraduate-cen-07-2025' => 'What is the RRB NTPC Undergraduate salary?',
            'rrb-group-d-cen-09-2025' => 'What is the Railway Group D salary?',
            'ssc-selection-posts-phase-xiv-2026' => 'What is SSC?',
            'canara-bank-graduate-apprentices-2026' => 'What is the Canara Bank apprentice salary or stipend?',
            'upsc-civil-services-mains-2026-admit-card' => 'Does an admit card confirm selection or salary?',
            'upsc-engineering-services-mains-2026-result' => 'What is the UPSC Engineering Services salary?',
        ];

        foreach ($expectedQuestions as $slug => $question) {
            $html = $this->get(route('notifications.show', ['exam' => $slug]))->assertOk()
                ->assertSee($question)->assertSee('15 October 2026')->assertSee('In-hand salary')
                ->getContent();
            $this->assertSame(1, substr_count($html, 'id="notification-salary"'));
            $this->assertSame(1, substr_count($html, 'id="notification-faqs"'));
        }

        $this->get(route('notifications.show', ['exam' => 'upsc']))
            ->assertSee('₹56,100')->assertSee('₹1,77,500')->assertSee('IAS_PAY_Rules2016.pdf');
        $this->get(route('notifications.show', ['exam' => 'rrb-ntpc-undergraduate-cen-07-2025']))
            ->assertSee('₹19,900–₹21,700')->assertSee('cited 2025 notice');
        $this->get(route('notifications.show', ['exam' => 'canara-bank-graduate-apprentices-2026']))
            ->assertSee('2026 apprentice stipend has not been verified')->assertSee('prior-cycle reference only');
        $this->get(route('notifications.show', ['exam' => 'upsc-engineering-services-mains-2026-result']))
            ->assertSee('does not establish a salary range')->assertDontSee('₹56,100');
    }

    public function test_government_job_categories_filter_guides_and_show_honest_empty_states(): void
    {
        $this->get(route('jobs.index'))->assertOk()->assertSee('Latest Government Jobs')
            ->assertSee('Official recruitment websites')->assertSee('Vacancies');

        foreach (['10th-pass' => 'rrb-group-d-cen-09-2025', '12th-pass' => 'rrb-ntpc-undergraduate-cen-07-2025', 'graduate' => 'rrb-ntpc-graduate-cen-06-2026', 'upcoming' => 'upsc'] as $category => $slug) {
            $this->get(route('jobs.category', ['category' => $category]))->assertOk()
                ->assertSee(route('notifications.show', ['exam' => $slug]), false);
        }

        $this->get(route('jobs.category', ['category' => '10th-pass']))->assertOk()
            ->assertDontSee('RRB NTPC Graduate — CEN 06/2026');
        foreach (['ssc' => 'SSC Selection Posts Phase XIV 2026', 'banking' => 'Canara Bank Graduate Apprentices 2026', 'admit-cards' => 'UPSC Civil Services Mains 2026 Admit Card', 'results' => 'UPSC Engineering Services Mains 2026 Written Result'] as $category => $title) {
            $this->get(route('jobs.category', ['category' => $category]))->assertOk()
                ->assertSee($title)->assertSee('15 October 2026')->assertDontSee('No verified');
        }
        $this->get(route('jobs.category', ['category' => 'admit-cards']))->assertDontSee('Written result published');
        $this->get(route('jobs.category', ['category' => 'results']))->assertDontSee('Archived admit card notice');
        $this->get('/government-jobs/invalid')->assertNotFound();
        $this->get('/notifications/2027')->assertRedirect(route('notifications.index'))->assertStatus(301);
    }

    public function test_hub_and_exam_pages_show_dated_official_sources_and_pending_rules(): void
    {
        $this->get(route('notifications.index'))->assertOk()
            ->assertSee('14 October 2026')->assertSee('UPSC Civil Services 2027')->assertSee('RRB NTPC Graduate')
            ->assertDontSee('notification-tabs')->assertDontSee('23 May 2027');
        $this->get(route('notifications.show', ['exam' => 'upsc']))->assertOk()
            ->assertSee('13 January 2027')->assertSee('23 May 2027')->assertSee('20 August 2027')
            ->assertSee('Prior-year eligibility reference: CSE 2026')->assertSee('not a promise about 2027')
            ->assertSee('Calendar-Year-2027-Engl-200526.pdf')->assertDontSee('Railway exams: plan for 2027');
        $this->get(route('notifications.show', ['exam' => 'rrb-ntpc-graduate-cen-06-2026']))->assertOk()
            ->assertSee('CEN 06/2026')->assertSee('1 January 2027')->assertSee('6 November 2026, 11:59 pm')
            ->assertSee('PRID=2321873')->assertDontSee('CEN 09/2025')->assertDontSee('CEN 07/2025');
        $this->get(route('notifications.show', ['exam' => 'rrb-ntpc-undergraduate-cen-07-2025']))->assertOk()
            ->assertSee('50% marks rule')->assertDontSee('CEN 06/2026')->assertDontSee('CEN 09/2025');
        $this->get(route('notifications.show', ['exam' => 'rrb-group-d-cen-09-2025']))->assertOk()
            ->assertSee('not confirmed rules for a fresh 2027 cycle')->assertSee('Prior notice')
            ->assertDontSee('CEN 06/2026')->assertDontSee('CEN 07/2025');
        foreach (ExamNotificationController::NOTICES as $slug => $notice) {
            $this->get(route('notifications.index'))->assertSee(route('notifications.show', ['exam' => $slug]), false);
        }
        $this->get('/notifications/2027/unknown')->assertNotFound();
    }

    public function test_exam_cards_lead_to_the_correct_notices(): void
    {
        foreach (['upsc' => 'upsc', 'railways' => 'railways', 'railway' => 'railways'] as $exam => $target) {
            $this->get(route('exam', $exam))->assertStatus(301)->assertRedirect(route('notifications.show', ['exam' => $target]));
        }
        $this->get('/')->assertOk()->assertSee(route('notifications.index'), false)
            ->assertSee(route('notifications.show', ['exam' => 'upsc']), false)
            ->assertSee(route('notifications.show', ['exam' => 'railways']), false)
            ->assertDontSee('href="/exams/upsc"', false)->assertDontSee('href="/exams/railways"', false)
            ->assertDontSee('href="/exams/ssc"', false)->assertDontSee('href="/exams/banking"', false);
    }

    public function test_notification_pages_have_canonicals_and_are_in_the_sitemap(): void
    {
        foreach ([route('notifications.index'), ...array_map(fn (string $slug): string => route('notifications.show', ['exam' => $slug]), ['railways', ...array_keys(ExamNotificationController::NOTICES)])] as $url) {
            $this->get($url)->assertOk()->assertSee('<link rel="canonical" href="'.$url.'">', false);
            $this->get(route('sitemap'))->assertOk()->assertSee($url, false);
        }
    }

    public function test_each_notification_page_has_distinct_metadata_and_one_main_heading(): void
    {
        $descriptions = [];
        foreach ([route('notifications.index'), ...array_map(fn (string $slug): string => route('notifications.show', ['exam' => $slug]), ['railways', ...array_keys(ExamNotificationController::NOTICES)])] as $url) {
            $html = $this->get($url)->assertOk()->assertDontSee('name="robots" content="noindex"', false)->getContent();
            $this->assertSame(1, preg_match_all('/<h1\b/i', $html));
            $this->assertSame(1, preg_match('/<meta name="description" content="([^"]+)"/', $html, $matches));
            $descriptions[] = $matches[1];
        }
        $this->assertCount(count(ExamNotificationController::NOTICES) + 2, array_unique($descriptions));
    }
}
