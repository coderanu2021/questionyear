<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\User;
use App\WordDocumentImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class WordImportTest extends TestCase
{
    use RefreshDatabase;

    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        parent::tearDown();
    }

    private function document(array $lines, string $extra = ''): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'word-import-');
        $this->temporaryFiles[] = $path;
        $body = '';
        foreach ($lines as $line) {
            $body .= '<w:p><w:r><w:t>'.htmlspecialchars($line, ENT_XML1).'</w:t></w:r></w:p>';
        }
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$body.$extra.'</w:body></w:document>');
        $zip->close();

        return new UploadedFile($path, 'example.docx', null, null, true);
    }

    public function test_notes_preserve_unicode_tables_and_escape_html_without_overwriting_existing_content(): void
    {
        $existing = Chapter::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('admin', ['page' => 'import']))->assertOk()->assertSee('Import Word file');
        $file = $this->document(['हड़प्पा civilisation', '<script>alert(1)</script>'], '<w:tbl><w:tr><w:tc><w:p><w:r><w:t>Harappa</w:t></w:r></w:p></w:tc></w:tr></w:tbl>');
        $this->post(route('admin.import.store'), ['mode' => 'notes', 'title' => 'Imported notes', 'subject_id' => $existing->subject_id, 'file' => $file])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('imported_url');
        $chapter = Chapter::where('title', 'Imported notes')->firstOrFail();
        $this->assertSame('draft', $chapter->status);
        $this->assertStringContainsString('हड़प्पा', $chapter->content);
        $this->assertStringContainsString('<table><tr><td><p>Harappa</p></td></tr></table>', $chapter->content);
        $this->assertStringNotContainsString('<script>', $chapter->content);
        $this->assertDatabaseHas('chapters', ['id' => $existing->id, 'title' => $existing->title]);
    }

    public function test_mcqs_import_correct_options_and_explanations(): void
    {
        $chapter = Chapter::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $file = $this->document(['Heading', '1. What is 2 + 2?', 'A. 1', 'B. 2', 'C. 3', 'D. 4', 'Answer: D', 'Explanation: Two plus two is four.', '2) Capital of India?', '(a) Delhi', '(b) Mumbai', '(c) Pune', '(d) Jaipur', 'Ans: Delhi']);
        $this->post(route('admin.import.store'), ['mode' => 'mcq', 'title' => 'Imported quiz', 'chapter_id' => $chapter->id, 'file' => $file])->assertSessionHasNoErrors();
        $quiz = Quiz::where('title', 'Imported quiz')->firstOrFail();
        $this->assertCount(2, $quiz->questions);
        $this->assertSame(3, $quiz->questions[0]['c']);
        $this->assertSame(0, $quiz->questions[1]['c']);
        $this->assertSame('Two plus two is four.', $quiz->questions[0]['explanation']);
    }

    public function test_answers_import_as_standalone_draft(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $file = $this->document(['1. Where is Harappa?', 'Answer: Punjab.', 'In present-day Pakistan.']);
        $this->post(route('admin.import.store'), ['mode' => 'qa', 'title' => 'History answers', 'qa_subject' => 'general-knowledge', 'file' => $file])->assertSessionHasNoErrors();
        $quiz = Quiz::firstOrFail();
        $this->assertNull($quiz->chapter_id);
        $this->assertFalse($quiz->isPublished());
        $this->assertSame("Punjab.\nIn present-day Pakistan.", $quiz->questions[0]['answer']);
    }

    public function test_incomplete_mcqs_do_not_save_partial_quizzes(): void
    {
        $chapter = Chapter::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $file = $this->document(['1. Valid?', 'A. Yes', 'B. No', 'C. Maybe', 'D. Never', 'Answer: A', '2. Missing answer?', 'A. One', 'B. Two', 'C. Three', 'D. Four']);
        $this->post(route('admin.import.store'), ['mode' => 'mcq', 'title' => 'Invalid', 'chapter_id' => $chapter->id, 'file' => $file])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_invalid_archives_are_rejected(): void
    {
        $chapter = Chapter::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post(route('admin.import.store'), ['mode' => 'notes', 'title' => 'Invalid', 'subject_id' => $chapter->subject_id, 'file' => UploadedFile::fake()->createWithContent('bad.docx', 'not a Word archive')])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('chapters', 1);
    }

    public function test_upload_requires_active_admin(): void
    {
        $this->post(route('admin.import.store'))->assertRedirect(route('login'));
        foreach ([['role' => 'student'], ['role' => 'admin', 'status' => 'blocked']] as $attributes) {
            $this->actingAs(User::factory()->create($attributes));
            $this->post(route('admin.import.store'))->assertForbidden();
            $this->get(route('admin', ['page' => 'import']))->assertForbidden();
        }
    }

    public function test_explicit_line_breaks_and_headings_are_preserved(): void
    {
        $file = $this->document([], '<w:p><w:pPr><w:pStyle w:val="Heading2"/></w:pPr><w:r><w:t>Title</w:t><w:br/><w:t>Next line</w:t></w:r></w:p>');
        $document = app(WordDocumentImporter::class)->extract($file->getPathname());
        $this->assertSame(['Title', 'Next line'], $document['lines']);
        $this->assertStringContainsString('<h2>Title<br>', $document['html']);
    }

    public function test_empty_documents_and_external_entities_are_rejected(): void
    {
        $chapter = Chapter::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = ['mode' => 'notes', 'title' => 'Invalid', 'subject_id' => $chapter->subject_id];
        $this->post(route('admin.import.store'), $data + ['file' => $this->document([])])->assertSessionHasErrors('file');
        $file = $this->document(['Placeholder']);
        $zip = new \ZipArchive;
        $zip->open($file->getPathname());
        $zip->addFromString('word/document.xml', '<!DOCTYPE document [<!ENTITY external SYSTEM "file:///secret">]><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>&external;</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();
        $this->post(route('admin.import.store'), $data + ['file' => $file])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('chapters', 1);
    }

    public function test_duplicate_options_and_unclear_answers_are_rejected(): void
    {
        $chapter = Chapter::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $data = ['mode' => 'mcq', 'title' => 'Invalid', 'chapter_id' => $chapter->id];
        foreach ([['A. One', 'A. Two', 'C. Three', 'D. Four', 'Answer: A'], ['A. One', 'B. Two', 'C. Three', 'D. Four', 'Answer: unknown']] as $options) {
            $this->post(route('admin.import.store'), $data + ['file' => $this->document(array_merge(['1. Which option?'], $options))])->assertSessionHasErrors('file');
        }
        $this->assertDatabaseCount('quizzes', 0);
    }
}
