<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_hub_and_exam_pages_show_dated_official_sources_and_pending_rules(): void
    {
        $this->get(route('notifications.index'))->assertOk()
            ->assertSee('14 October 2026')->assertSee('UPSC Civil Services 2027')->assertSee('Railway exams: plan for 2027');
        $this->get(route('notifications.show', ['exam' => 'upsc']))->assertOk()
            ->assertSee('13 January 2027')->assertSee('23 May 2027')->assertSee('20 August 2027')
            ->assertSee('Prior-year eligibility reference: CSE 2026')->assertSee('not a promise about 2027')
            ->assertSee('Calendar-Year-2027-Engl-200526.pdf')->assertDontSee('Railway exams: plan for 2027');
        $this->get(route('notifications.show', ['exam' => 'railways']))->assertOk()
            ->assertSee('CEN 06/2026')->assertSee('1 January 2027')->assertSee('6 November 2026, 11:59 pm')
            ->assertSee('Prior notice')->assertSee('not confirmed rules for a fresh 2027 cycle')
            ->assertSee('PRID=2321873')->assertDontSee('UPSC Civil Services 2027');
        $this->get('/notifications/2027/unknown')->assertNotFound();
    }

    public function test_exam_cards_lead_to_the_correct_notices(): void
    {
        foreach (['upsc' => 'upsc', 'railways' => 'railways', 'railway' => 'railways'] as $exam => $target) {
            $this->get(route('exam', $exam))->assertRedirect(route('notifications.show', ['exam' => $target]));
        }
        $this->get('/')->assertOk()->assertSee(route('notifications.index'), false);
    }

    public function test_notification_pages_have_canonicals_and_are_in_the_sitemap(): void
    {
        foreach ([route('notifications.index'), route('notifications.show', ['exam' => 'upsc']), route('notifications.show', ['exam' => 'railways'])] as $url) {
            $this->get($url)->assertOk()->assertSee('<link rel="canonical" href="'.$url.'">', false);
            $this->get(route('sitemap'))->assertOk()->assertSee($url, false);
        }
    }

    public function test_each_notification_page_has_distinct_metadata_and_one_main_heading(): void
    {
        $descriptions = [];
        foreach ([route('notifications.index'), route('notifications.show', ['exam' => 'upsc']), route('notifications.show', ['exam' => 'railways'])] as $url) {
            $html = $this->get($url)->assertOk()->assertDontSee('name="robots" content="noindex"', false)->getContent();
            $this->assertSame(1, preg_match_all('/<h1\b/i', $html));
            $this->assertSame(1, preg_match('/<meta name="description" content="([^"]+)"/', $html, $matches));
            $descriptions[] = $matches[1];
        }
        $this->assertCount(3, array_unique($descriptions));
    }
}
