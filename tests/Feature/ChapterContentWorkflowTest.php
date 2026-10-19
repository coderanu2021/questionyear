<?php

namespace Tests\Feature;

use App\ChapterContentGenerator;
use App\ChapterContentWorkflow;
use App\Jobs\GenerateChapterSection;
use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class ChapterContentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    /** @return array<string, mixed> */
    private function brief(Chapter $chapter): array
    {
        return ['chapter_id' => $chapter->id, 'size' => 'small', 'syllabus' => ['Introduction', 'Basic concepts', 'Applications'], 'sources' => [['id' => 'reference-1', 'title' => 'Test source fixture', 'url' => 'https://example.edu/textbook', 'locator' => 'Test edition, chapter 1, pages 1–10', 'excerpt' => str_repeat('Source evidence fixture for automated workflow testing. ', 5)]]];
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        $sections = [];
        foreach (['Introduction', 'Basic concepts', 'Applications'] as $heading) {
            $html = '<h3>Understanding '.e($heading).'</h3>';
            for ($paragraph = 0; $paragraph < 12; $paragraph++) {
                $html .= '<p>';
                for ($sentence = 0; $sentence < 5; $sentence++) {
                    $html .= fake()->words(12, true).'. ';
                }
                $html .= '</p>';
            }
            $sections[] = ['heading' => $heading, 'html' => $html, 'source_ids' => ['reference-1']];
        }

        return ['seo_title' => 'Complete test learning resource', 'meta_description' => 'Study concepts with examples, revision notes and explained practice questions.', 'slug' => 'complete-test-learning-resource', 'sections' => $sections, 'exam_facts' => array_map(fn (int $index): string => 'Test fact fixture '.$index.'.', range(1, 5)), 'misconceptions' => array_map(fn (int $index): string => 'Test misconception fixture '.$index.'.', range(1, 3)), 'summary' => array_map(fn (int $index): string => 'Test summary fixture '.$index.'.', range(1, 5)), 'questions' => array_map(fn (int $index): array => ['q' => 'Test question '.$index.'?', 'o' => ['First', 'Second', 'Third', 'Fourth'], 'c' => $index % 4, 'explanation' => 'Test answer explanation.', 'source_ids' => ['reference-1']], range(1, 20)), 'faqs' => array_map(fn (int $index): array => ['q' => 'Test FAQ '.$index.'?', 'a' => 'Test FAQ answer.'], range(1, 5)), 'uncertainties' => []];
    }

    /** @return array<string, mixed> */
    private function draft(Chapter $chapter, User $admin): array
    {
        $workflow = app(ChapterContentWorkflow::class);
        $id = $workflow->start([$this->brief($chapter)], $admin->id, false)[0];
        $workflow->save($id, 1, $this->payload());

        return $workflow->revision($id);
    }

    /** @return array<string, mixed> */
    private function verification(int $version): array
    {
        return ['version' => $version, 'facts_checked' => 1, 'syllabus_checked' => 1, 'originality_checked' => 1, 'readability_checked' => 1, 'review_note' => 'Checked source fixture edition and all answers, syllabus coverage, originality and readability.'];
    }

    public function test_review_tools_require_an_active_admin(): void
    {
        $this->get(route('admin.content.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.content.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'blocked']))->get(route('admin.content.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.content.index'))->assertOk()->assertSee('Subject-wise inventory');
    }

    public function test_three_chapter_batch_queues_work_without_altering_public_content(): void
    {
        Queue::fake();
        config(['services.gemini.key' => 'test-key']);
        $chapters = Chapter::factory()->count(3)->create();
        $before = $chapters->map(fn (Chapter $chapter): array => $chapter->fresh()->toArray())->all();
        $this->actingAs($this->admin())->post(route('admin.content.store'), ['mode' => 'generate', 'batch' => json_encode($chapters->map(fn (Chapter $chapter): array => $this->brief($chapter))->all())])->assertRedirect();
        Queue::assertPushed(GenerateChapterSection::class, 3);
        $this->assertSame($before, $chapters->map(fn (Chapter $chapter): array => $chapter->fresh()->toArray())->all());
        $this->assertSame(1, DB::table('chapter_content_revisions')->distinct()->count('batch_id'));
        $this->assertDatabaseCount('chapters', 3);
    }

    public function test_duplicate_or_oversized_batches_are_rejected_atomically(): void
    {
        $admin = $this->admin();
        $chapters = Chapter::factory()->count(6)->create();
        $items = $chapters->map(fn (Chapter $chapter): array => $this->brief($chapter))->all();
        $this->actingAs($admin)->post(route('admin.content.store'), ['mode' => 'generate', 'batch' => json_encode($items)])->assertSessionHasErrors();
        $this->assertDatabaseCount('chapter_content_revisions', 0);
        $workflow = app(ChapterContentWorkflow::class);
        $workflow->start([$items[1]], $admin->id, false);
        try {
            $workflow->start([$items[0], $items[1]], $admin->id, false);
            $this->fail('Duplicate working revisions must be blocked.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('chapter_content_revisions', 1);
        }
    }

    public function test_import_creates_a_review_revision_and_preserves_the_existing_chapter(): void
    {
        $chapter = Chapter::factory()->create();
        $original = $chapter->fresh()->toArray();
        $item = [...$this->brief($chapter), 'payload' => $this->payload()];
        $this->actingAs($this->admin())->post(route('admin.content.store'), ['mode' => 'import', 'batch' => json_encode([$item])])->assertRedirect();
        $revision = app(ChapterContentWorkflow::class)->revision(DB::table('chapter_content_revisions')->value('id'));
        $this->assertSame('review', $revision['status']);
        $this->assertSame([], $revision['checks']['errors']);
        $this->assertSame($original, $chapter->fresh()->toArray());
        $this->get(route('admin.content.show', $revision['id']))->assertOk()->assertSee('Test FAQ 1?')->assertSee('Human verification');
    }

    public function test_verification_requires_attestations_and_blocks_uncertain_claims(): void
    {
        $admin = $this->admin();
        $chapter = Chapter::factory()->create();
        $revision = $this->draft($chapter, $admin);
        $this->actingAs($admin)->post(route('admin.content.verify', $revision['id']), ['version' => 2, 'review_note' => str_repeat('Review ', 10)])->assertSessionHasErrors('facts_checked');
        $payload = $revision['payload'];
        $payload['uncertainties'] = ['This date needs checking.'];
        app(ChapterContentWorkflow::class)->save($revision['id'], 2, $payload);
        $this->post(route('admin.content.verify', $revision['id']), $this->verification(3))->assertSessionHasErrors('quality');
        $this->assertDatabaseHas('chapter_content_revisions', ['id' => $revision['id'], 'status' => 'review', 'verified_at' => null]);
    }

    public function test_verified_publish_requires_replacement_approval_and_is_not_repeatable(): void
    {
        $admin = $this->admin();
        $chapter = Chapter::factory()->create();
        $existingQuiz = Quiz::factory()->for($chapter)->create();
        $revision = $this->draft($chapter, $admin);
        $this->actingAs($admin)->post(route('admin.content.publish', $revision['id']), ['version' => 2, 'approve_replacement' => 1])->assertConflict();
        $this->post(route('admin.content.verify', $revision['id']), $this->verification(2))->assertRedirect();
        $this->post(route('admin.content.publish', $revision['id']), ['version' => 3])->assertSessionHasErrors('replacement');
        $this->post(route('admin.content.publish', $revision['id']), ['version' => 3, 'approve_replacement' => 1])->assertRedirect();
        $this->assertDatabaseHas('chapter_content_revisions', ['id' => $revision['id'], 'status' => 'published']);
        $this->assertNotNull($chapter->fresh()->content_verified_at);
        $this->assertDatabaseHas('quizzes', ['id' => $existingQuiz->id]);
        $this->assertDatabaseCount('quizzes', 2);
        $this->assertSame('<p>Chapter notes.</p>', app(ChapterContentWorkflow::class)->revision($revision['id'])['original']['content']);
        $this->post(route('admin.content.publish', $revision['id']), ['version' => 3, 'approve_replacement' => 1])->assertConflict();
        $this->assertDatabaseCount('quizzes', 2);
        $this->get($chapter->readingUrl(0))->assertOk()->assertSee('application/ld+json', false)->assertSee('LearningResource')->assertSee('Frequently asked questions')->assertSee('Sources and references');
        $state = $this->get(route('admin'))->viewData('state');
        $this->putJson(route('admin.save'), $state)->assertOk();
        $reviewedQuizId = $chapter->fresh()->content_evidence['quiz_id'];
        $quizIndex = collect($state['tests'])->search(fn (array $quiz): bool => $quiz['id'] === $reviewedQuizId);
        $state['tests'][$quizIndex]['qs'][0]['explanation'] = 'Unreviewed answer change.';
        $this->putJson(route('admin.save'), $state)->assertUnprocessable();
        $this->assertSame('Test answer explanation.', Quiz::findOrFail($reviewedQuizId)->questions[0]['explanation']);
        $workflow = app(ChapterContentWorkflow::class);
        $nextId = $workflow->start([$this->brief($chapter)], $admin->id, false)[0];
        $this->assertSame($workflow->hash($workflow->snapshot($chapter->fresh())), $workflow->revision($nextId)['original_hash']);
    }

    public function test_edits_invalidate_verification_and_stale_edits_are_rejected(): void
    {
        $admin = $this->admin();
        $revision = $this->draft(Chapter::factory()->create(), $admin);
        $this->actingAs($admin)->post(route('admin.content.verify', $revision['id']), $this->verification(2))->assertRedirect();
        $this->put(route('admin.content.update', $revision['id']), ['version' => 2, 'payload' => json_encode($revision['payload'])])->assertConflict();
        $this->put(route('admin.content.update', $revision['id']), ['version' => 3, 'payload' => json_encode($revision['payload'])])->assertRedirect();
        $this->assertDatabaseHas('chapter_content_revisions', ['id' => $revision['id'], 'status' => 'review', 'verified_hash' => null, 'verified_at' => null]);
    }

    public function test_explicit_pending_review_publication_is_labelled_and_never_records_verification(): void
    {
        $admin = $this->admin();
        $chapter = Chapter::factory()->create();
        $revision = $this->draft($chapter, $admin);
        $this->actingAs($admin);
        $this->post(route('admin.content.publish', $revision['id']), ['version' => 2, 'approve_replacement' => 1, 'publish_pending_review' => 1])->assertSessionHasErrors('publication_note');
        $this->post(route('admin.content.publish', $revision['id']), ['version' => 2, 'publish_pending_review' => 1, 'publication_note' => 'Owner requests publication now; human factual review will follow.'])->assertSessionHasErrors('replacement');
        $this->post(route('admin.content.publish', $revision['id']), ['version' => 2, 'approve_replacement' => 1, 'publish_pending_review' => 1, 'publication_note' => 'Owner requests publication now; human factual review will follow.'])->assertRedirect();
        $chapter->refresh();
        $this->assertNull($chapter->content_verified_at);
        $this->assertNull($chapter->content_evidence['reviewer_id']);
        $this->assertSame('pending', $chapter->content_evidence['review_status']);
        $this->assertSame($admin->id, $chapter->content_evidence['publication_authorization']['publisher_id']);
        $this->assertStringContainsString('factual review pending', $chapter->quizzes()->sole()->title);
        $this->assertDatabaseHas('chapter_content_revisions', ['id' => $revision['id'], 'status' => 'published', 'verified_at' => null, 'verified_by' => null, 'verified_hash' => null]);
        $this->get($chapter->readingUrl(0))->assertOk()->assertSee('Factual review pending')->assertSee('Sources and references')->assertDontSee('LearningResource');
        $this->post(route('admin.content.publish', $revision['id']), ['version' => 2, 'approve_replacement' => 1, 'publish_pending_review' => 1, 'publication_note' => 'Owner requests publication now; human factual review will follow.'])->assertConflict();
        $this->assertDatabaseCount('quizzes', 1);
    }

    public function test_pending_review_publication_still_blocks_quality_failures_and_stale_content(): void
    {
        $admin = $this->admin();
        $chapter = Chapter::factory()->create();
        $revision = $this->draft($chapter, $admin);
        $workflow = app(ChapterContentWorkflow::class);
        $payload = $revision['payload'];
        $payload['uncertainties'] = ['An important claim remains unresolved.'];
        $workflow->save($revision['id'], 2, $payload);
        $request = ['version' => 3, 'approve_replacement' => 1, 'publish_pending_review' => 1, 'publication_note' => 'Owner requests publication now; human factual review will follow.'];
        $this->actingAs($admin)->post(route('admin.content.publish', $revision['id']), $request)->assertSessionHasErrors('quality');
        $workflow->save($revision['id'], 3, $revision['payload']);
        $request['version'] = 4;
        $chapter->update(['content' => '<p>A later editorial change.</p>']);
        $this->post(route('admin.content.publish', $revision['id']), $request)->assertConflict();
        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_pending_review_publication_cannot_replace_a_verified_chapter(): void
    {
        $admin = $this->admin();
        $chapter = Chapter::factory()->create(['content_verified_at' => now()]);
        $revision = $this->draft($chapter, $admin);
        $this->actingAs($admin)->post(route('admin.content.publish', $revision['id']), ['version' => 2, 'approve_replacement' => 1, 'publish_pending_review' => 1, 'publication_note' => 'Owner requests publication now; human factual review will follow.'])->assertConflict();
        $this->assertSame('<p>Chapter notes.</p>', $chapter->fresh()->content);
        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_live_changes_since_generation_block_publication(): void
    {
        $admin = $this->admin();
        $chapter = Chapter::factory()->create();
        $revision = $this->draft($chapter, $admin);
        $this->actingAs($admin)->post(route('admin.content.verify', $revision['id']), $this->verification(2))->assertRedirect();
        $chapter->update(['content' => '<p>A human updated the live chapter.</p>']);
        $this->post(route('admin.content.publish', $revision['id']), ['version' => 3, 'approve_replacement' => 1])->assertConflict();
        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_structural_and_duplicate_checks_prevent_verification(): void
    {
        $admin = $this->admin();
        $chapter = Chapter::factory()->create();
        $revision = $this->draft($chapter, $admin);
        $payload = $revision['payload'];
        $payload['sections'][1]['html'] = $payload['sections'][0]['html'];
        $payload['questions'][1]['q'] = $payload['questions'][0]['q'];
        $payload['sections'][0]['source_ids'] = ['invented-source'];
        $workflow = app(ChapterContentWorkflow::class);
        $workflow->save($revision['id'], 2, $payload);
        $this->actingAs($admin)->post(route('admin.content.verify', $revision['id']), $this->verification(3))->assertSessionHasErrors('quality');
        $errors = $workflow->revision($revision['id'])['checks']['errors'];
        $this->assertContains('Repeated MCQ questions.', $errors);
        $this->assertContains('Repeated explanatory paragraph.', $errors);
        $this->assertContains('A section or MCQ cites a source outside the supplied source dossier.', $errors);
        $payload['questions'] = array_slice($payload['questions'], 0, 19);
        $this->put(route('admin.content.update', $revision['id']), ['version' => 3, 'payload' => json_encode($payload)])->assertSessionHasErrors('questions');
    }

    public function test_imported_html_is_sanitized_before_preview_and_publication(): void
    {
        $admin = $this->admin();
        $revision = $this->draft(Chapter::factory()->create(), $admin);
        $payload = $revision['payload'];
        $payload['sections'][0]['html'] .= '<script>alert(1)</script><p onclick="alert(2)">Safe text</p><a href="javascript:alert(3)">link</a>';
        app(ChapterContentWorkflow::class)->save($revision['id'], 2, $payload);
        $html = app(ChapterContentWorkflow::class)->revision($revision['id'])['payload']['sections'][0]['html'];
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_section_jobs_checkpoint_and_ignore_duplicate_delivery(): void
    {
        Queue::fake();
        Http::preventStrayRequests();
        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'test-model']);
        $workflow = app(ChapterContentWorkflow::class);
        $id = $workflow->start([$this->brief(Chapter::factory()->create())], $this->admin()->id)[0];
        $section = $this->payload()['sections'][0] + ['uncertainties' => []];
        Http::fake(['*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode($section)]]]]]])]);
        $job = new GenerateChapterSection($id, 0);
        $job->handle($workflow, app(ChapterContentGenerator::class));
        $job->handle($workflow, app(ChapterContentGenerator::class));
        Http::assertSentCount(1);
        $this->assertSame(1, $workflow->revision($id)['payload']['_completed']);
        Queue::assertPushed(GenerateChapterSection::class, fn ($next): bool => $next->stage === 1);
    }

    public function test_unavailable_model_uses_an_advertised_fallback_and_caches_it(): void
    {
        Queue::fake();
        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'missing-model']);
        $workflow = app(ChapterContentWorkflow::class);
        $id = $workflow->start([$this->brief(Chapter::factory()->create())], $this->admin()->id)[0];
        $section = $this->payload()['sections'][0] + ['uncertainties' => []];
        Http::fake([
            '*/models/missing-model:generateContent' => Http::response([], 404),
            '*/models/gemini-2.5-flash-lite:generateContent' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode($section)]]]]]]),
            '*/v1beta/models*' => Http::response(['models' => [['name' => 'models/gemini-2.5-flash-lite', 'supportedGenerationMethods' => ['generateContent']]]]),
        ]);
        $generator = app(ChapterContentGenerator::class);
        $this->assertEquals($section, $generator->generate($workflow->revision($id), 0));
        $this->assertEquals($section, $generator->generate($workflow->revision($id), 0));
        Http::assertSentCount(4);
        $this->assertDatabaseHas('chapter_content_revisions', ['id' => $id, 'status' => 'draft']);
    }

    public function test_truncated_provider_output_is_not_committed_and_failed_jobs_can_resume(): void
    {
        Queue::fake();
        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'test-model']);
        Http::fake(['*' => Http::response(['candidates' => [['finishReason' => 'MAX_TOKENS', 'content' => ['parts' => [['text' => '{']]]]]])]);
        $workflow = app(ChapterContentWorkflow::class);
        $admin = $this->admin();
        $id = $workflow->start([$this->brief(Chapter::factory()->create())], $admin->id)[0];
        $job = new GenerateChapterSection($id, 0);
        try {
            $job->handle($workflow, app(ChapterContentGenerator::class));
            $this->fail('Truncated output must fail.');
        } catch (RuntimeException $exception) {
            $job->failed($exception);
        }
        $this->assertSame([], $workflow->revision($id)['payload']);
        $this->assertSame('Provider output was incomplete or blocked; section retained for retry.', $workflow->revision($id)['last_error']);
        $this->assertDatabaseHas('chapter_content_revisions', ['id' => $id, 'generation_state' => 'failed']);
        $this->actingAs($admin)->post(route('admin.content.resume', $id), ['version' => 1])->assertRedirect();
        Queue::assertPushed(GenerateChapterSection::class, 2);
    }

    public function test_audit_is_read_only_and_flags_missing_evidence(): void
    {
        $chapter = Chapter::factory()->create(['content' => null]);
        $this->artisan('chapters:audit', ['--json' => true])->expectsOutputToContain('Missing explanation')->assertSuccessful();
        $this->assertContains('No recorded factual verification', app(ChapterContentWorkflow::class)->audit($chapter)['issues']);
        $this->assertNull($chapter->fresh()->content);
    }

    public function test_old_admin_editor_cannot_overwrite_or_delete_verified_chapters(): void
    {
        $chapter = Chapter::factory()->create(['content_verified_at' => now()]);
        $this->actingAs($this->admin());
        $state = $this->get(route('admin'))->viewData('state');
        $this->putJson(route('admin.save'), $state)->assertOk();
        $state['chapters'][0]['content'] = '<p>Unreviewed replacement.</p>';
        $this->putJson(route('admin.save'), $state)->assertUnprocessable();
        $state['chapters'] = [];
        $this->putJson(route('admin.save'), $state)->assertUnprocessable();
        $this->assertSame('<p>Chapter notes.</p>', $chapter->fresh()->content);
    }

    public function test_existing_general_knowledge_chapters_can_be_read_without_changing_quiz_routes(): void
    {
        $subject = Subject::factory()->create(['name' => 'General Knowledge', 'slug' => 'general-knowledge']);
        $chapter = Chapter::factory()->for($subject)->create(['content_verified_at' => now()]);
        $this->get($chapter->readingUrl(0))->assertOk()->assertSee('Chapter notes.');
    }

    public function test_generation_merges_all_sections_and_twenty_mcqs_into_review_only(): void
    {
        Queue::fake();
        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'test-model']);
        $chapter = Chapter::factory()->create();
        $workflow = app(ChapterContentWorkflow::class);
        $id = $workflow->start([$this->brief($chapter)], $this->admin()->id)[0];
        $payload = $this->payload();
        $responses = [];
        foreach ($payload['sections'] as $section) {
            $responses[] = $section + ['uncertainties' => []];
        }
        $extras = $payload;
        unset($extras['sections'], $extras['questions']);
        $responses[] = $extras;
        $responses[] = ['questions' => array_slice($payload['questions'], 0, 10), 'uncertainties' => []];
        $responses[] = ['questions' => array_slice($payload['questions'], 10), 'uncertainties' => []];
        $sequence = Http::sequence();
        foreach ($responses as $response) {
            $sequence->push(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode($response)]]]]]]);
        }
        Http::fake(['*' => $sequence]);
        for ($stage = 0; $stage < 6; $stage++) {
            (new GenerateChapterSection($id, $stage))->handle($workflow, app(ChapterContentGenerator::class));
        }
        $revision = $workflow->revision($id);
        $this->assertSame('review', $revision['status']);
        $this->assertSame('complete', $revision['generation_state']);
        $this->assertCount(20, $revision['payload']['questions']);
        $this->assertSame([], $revision['checks']['errors']);
        $this->assertSame('<p>Chapter notes.</p>', $chapter->fresh()->content);
        $this->assertDatabaseCount('quizzes', 0);
        Http::assertSentCount(6);
    }

    public function test_source_wording_overlap_and_near_duplicate_content_are_flagged(): void
    {
        $chapter = Chapter::factory()->create();
        $workflow = app(ChapterContentWorkflow::class);
        $payload = $this->payload();
        $brief = $this->brief($chapter);
        $brief['sources'][0]['excerpt'] = strip_tags($payload['sections'][0]['html']);
        Chapter::factory()->create(['content' => implode(' ', array_column($payload['sections'], 'html'))]);
        $checks = $workflow->quality->check($chapter, $payload, $brief);
        $this->assertContains('A long passage matches supplied source wording; rewrite it in original language.', $checks['errors']);
        $this->assertContains('Substantial wording overlap with chapter #2', $checks['errors']);
    }

    public function test_archiving_retains_history_and_allows_a_corrected_brief(): void
    {
        $admin = $this->admin();
        $chapter = Chapter::factory()->create();
        $revision = $this->draft($chapter, $admin);
        $this->actingAs($admin)->post(route('admin.content.archive', $revision['id']), ['version' => 2])->assertRedirect();
        $this->assertDatabaseHas('chapter_content_revisions', ['id' => $revision['id'], 'status' => 'archived']);
        $this->put(route('admin.content.update', $revision['id']), ['version' => 3, 'payload' => json_encode($revision['payload'])])->assertConflict();
        $workflow = app(ChapterContentWorkflow::class);
        $newId = $workflow->start([$this->brief($chapter)], $admin->id, false)[0];
        $this->assertNotSame($revision['id'], $newId);
        $this->assertDatabaseCount('chapter_content_revisions', 2);
        $this->assertDatabaseCount('chapters', 1);
    }

    public function test_editing_the_source_dossier_clears_verification(): void
    {
        $admin = $this->admin();
        $chapter = Chapter::factory()->create();
        $revision = $this->draft($chapter, $admin);
        $this->actingAs($admin)->post(route('admin.content.verify', $revision['id']), $this->verification(2))->assertRedirect();
        $brief = $revision['brief'];
        $brief['sources'][0]['locator'] = 'Corrected edition, pages 11–20';
        $this->put(route('admin.content.update', $revision['id']), ['version' => 3, 'payload' => json_encode($revision['payload']), 'brief' => json_encode($brief)])->assertRedirect();
        $saved = app(ChapterContentWorkflow::class)->revision($revision['id']);
        $this->assertSame('review', $saved['status']);
        $this->assertNull($saved['verified_at']);
        $this->assertSame($brief['sources'][0]['locator'], $saved['brief']['sources'][0]['locator']);
    }

    public function test_reviewed_general_knowledge_guides_are_discoverable_and_bulk_saves_preserve_their_quizzes(): void
    {
        $admin = $this->admin();
        $subject = Subject::factory()->create(['name' => 'General Knowledge', 'slug' => 'general-knowledge']);
        $chapter = Chapter::factory()->for($subject)->create();
        $unreviewed = Chapter::factory()->for($subject)->create();
        $revision = $this->draft($chapter, $admin);
        $this->actingAs($admin)->post(route('admin.content.verify', $revision['id']), $this->verification(2))->assertRedirect();
        $this->post(route('admin.content.publish', $revision['id']), ['version' => 3, 'approve_replacement' => 1])->assertRedirect();
        $this->get(route('subject', $subject->slug))->assertOk()->assertSee('Reviewed chapter guides')->assertSee($chapter->readingUrl(0));
        $this->get($chapter->readingUrl(0))->assertOk()->assertSee('Frequently asked questions')->assertDontSee('<li><a href="'.e($unreviewed->readingUrl(1)).'">', false);
        $state = $this->get(route('admin'))->viewData('state');
        $this->putJson(route('admin.save'), $state)->assertOk();
    }
}
