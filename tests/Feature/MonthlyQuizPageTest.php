<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonthlyQuizPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-13 12:00:00', 'Asia/Kolkata'));
        Http::preventStrayRequests();
    }

    public function test_all_200_questions_are_server_rendered_in_one_form_with_month_specific_metadata(): void
    {
        $id = $this->storeSet('2026-10-01');
        $response = $this->get(route('monthly'))->assertOk()
            ->assertSee('<title>October 2026 Monthly GK Quiz | 200 MCQs with Answers</title>', false)
            ->assertSee('October 2026 Monthly Quiz – 200 Important MCQs')
            ->assertSee('Practice 200 important GK, GS and Current Affairs MCQs for October 2026.')
            ->assertSee('property="og:title" content="October 2026 Monthly GK Quiz | 200 MCQs with Answers"', false)
            ->assertSee('name="twitter:card" content="summary"', false)
            ->assertSee('rel="canonical" href="'.route('monthly.set', ['set' => $id]).'"', false)
            ->assertSee('property="og:url" content="'.route('monthly.set', ['set' => $id]).'"', false)
            ->assertSee('action="'.route('monthly.set.submit', ['set' => $id]).'"', false)
            ->assertDontSee('Private explanation')
            ->assertDontSee('name="robots" content="noindex', false);

        $html = $response->getContent();
        $this->assertSame(1, preg_match_all('/<h1\b/', $html));
        $this->assertSame(1, preg_match_all('/<form\b/', $html));
        $this->assertSame(200, substr_count($html, 'data-question-index='));
        $this->assertSame(10, substr_count($html, 'data-question-batch'));
        $this->assertSame(800, substr_count($html, 'type="radio"'));
        $this->assertSame(1, substr_count($html, 'rel="canonical"'));
        $this->assertStringContainsString('October question 200', $html);
        $this->assertStringContainsString('name="answers[199]" value="-1"', $html);
        $this->assertDoesNotMatchRegularExpression('/<fieldset[^>]*\bhidden\b/', $html);

        if ($previewPath = getenv('MONTHLY_QUIZ_PREVIEW_PATH')) {
            file_put_contents($previewPath, $html);
        }
        Http::assertNothingSent();
    }

    public function test_months_and_existing_sets_keep_independent_metadata_drafts_scores_and_explanations(): void
    {
        $september = $this->storeSet('2026-09-01');
        $october = $this->storeSet('2026-10-01');
        $extra = $this->storeSet('2026-10-01', 2);
        $draftKeys = [];
        foreach ([[$september, 'September 2026', '2026-09-01', 1], [$october, 'October 2026', '2026-10-01', 1], [$extra, 'October 2026', '2026-10-01', 2]] as [$id, $month, $date, $number]) {
            $url = route('monthly.set', ['set' => $id]);
            $response = $this->get($url.'?page=2&utm_source=test')->assertOk()
                ->assertSee($month.' Monthly GK Quiz | 200 MCQs with Answers'.($number > 1 ? ' · Set '.$number : ''))
                ->assertSee('rel="canonical" href="'.$url.'"', false);
            preg_match('/data-draft-key="([^"]+)"/', $response->getContent(), $matches);
            $draftKeys[] = $matches[1];
            $answers = array_column($response->viewData('questions'), 'c');
            $answers[0] = -1;
            $answers[199] = ($answers[199] + 1) % 4;
            $payload = ['date' => $date, 'set_number' => $number, 'answers' => $answers, 'score' => 200];
            $this->post(route('monthly.set.submit', ['set' => $id]), $payload)->assertRedirect($url);
            $this->post(route('monthly.set.submit', ['set' => $id]), $payload)->assertRedirect($url);
            $this->assertDatabaseHas('daily_quiz_attempts', ['period' => 'monthly', 'quiz_date' => $date, 'set_number' => $number, 'score' => 198, 'total' => 200]);
            $this->get($url)->assertOk()->assertViewHas('questions', [])
                ->assertSee('198/200 marks')->assertSee('Private explanation 200')
                ->assertSee('Your answer: Skipped')->assertSee('data-completed-draft=', false)
                ->assertSee($month.' Monthly GK Quiz | 200 MCQs with Answers');
        }
        $this->assertCount(3, array_unique($draftKeys));
        $this->assertDatabaseCount('practice_sets', 3);
        $this->assertDatabaseCount('daily_quiz_attempts', 3);
    }

    public function test_validation_errors_keep_answers_and_question_content(): void
    {
        $id = $this->storeSet('2026-10-01');
        $url = route('monthly.set', ['set' => $id]);
        $this->get($url)->assertOk();
        $answers = array_fill(0, 200, -1);
        $answers[0] = 2;
        $this->from($url)->post(route('monthly.set.submit', ['set' => $id]), ['date' => '2026-09-01', 'set_number' => 1, 'answers' => $answers])->assertSessionHasErrors('date');
        $response = $this->get($url)->assertOk()->assertSee('data-has-old-answers="true"', false);
        $this->assertMatchesRegularExpression('/name="answers\[0\]" value="2"\s+checked/', $response->getContent());
        $this->assertDatabaseCount('daily_quiz_attempts', 0);
    }

    public function test_partial_and_empty_months_do_not_claim_200_published_questions(): void
    {
        $this->storeSet('2026-09-01');
        $this->get(route('monthly'))->assertOk()
            ->assertSee('October 2026 Monthly GK Quiz | Coming Soon')
            ->assertSee('name="robots" content="noindex,follow"', false);
        $this->storeSet('2026-10-01', count: 21);
        $this->get(route('monthly'))->assertOk()->assertSee('October 2026 Monthly GK Quiz | 21 MCQs with Answers')
            ->assertSee('October 2026 Monthly Quiz – 21 Important MCQs')
            ->assertSee('This set currently contains 21 published questions.');
    }

    public function test_daily_and_weekly_pages_do_not_load_monthly_assets(): void
    {
        foreach (['daily', 'weekly'] as $period) {
            $this->get(route($period))->assertOk()->assertDontSee('monthly-quiz.js')->assertDontSee('data-monthly-quiz');
        }
    }

    private function storeSet(string $date, int $number = 1, int $count = 200): int
    {
        $month = Carbon::parse($date)->format('F');
        $questions = array_map(fn (int $index): array => ['q' => $month.' question '.$index, 'o' => ['First answer', 'Second answer', 'Third answer', 'Fourth answer'], 'c' => $index % 4, 'explanation' => 'Private explanation '.$index], range(1, $count));

        return DB::table('practice_sets')->insertGetId(['period' => 'monthly', 'starts_on' => $date, 'set_number' => $number, 'questions' => json_encode($questions), 'created_at' => now(), 'updated_at' => now()]);
    }
}
