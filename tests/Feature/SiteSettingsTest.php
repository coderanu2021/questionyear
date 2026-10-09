<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\User;
use App\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_site_name_and_contact_email_appear_on_public_pages(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('<title>Free GK MCQs &amp; Chapter Notes | questionyear</title>', false)->assertSee('mailto:questionyear2026@gmail.com', false)->assertDontSee('QuizHub');
        foreach (['/contact', '/about', '/daily-quiz', '/upcoming', '/missing-page'] as $url) {
            $this->get($url)->assertSee('questionyear')->assertDontSee('QuizHub');
        }
        $this->get('/contact')->assertSee('Email us at')->assertSee('questionyear2026@gmail.com');
    }

    public function test_only_active_admins_can_access_and_update_settings(): void
    {
        $this->get('/admin/settings')->assertRedirect(route('login'));
        $this->putJson(route('admin.settings.update'), $this->payload())->assertUnauthorized();
        foreach ([['role' => 'student'], ['role' => 'admin', 'status' => 'inactive'], ['role' => 'admin', 'status' => 'blocked']] as $attributes) {
            $this->actingAs(User::factory()->create($attributes))->get('/admin/settings')->assertForbidden();
            $this->putJson(route('admin.settings.update'), $this->payload())->assertForbidden();
        }
        $this->assertSame('questionyear', SiteSettings::values()['site_title']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/settings')->assertOk()->assertSee('Website settings')->assertSee('name="logo"', false)->assertSee('Save settings');
    }

    public function test_saved_branding_and_content_update_across_the_website_without_overriding_chapter_seo(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $payload = [...$this->payload(), 'site_title' => 'My Learning Site', 'site_description' => 'Updated default SEO description.', 'contact_email' => 'support@example.com', 'home_title' => 'A new homepage heading', 'home_description' => 'Our new introduction.', 'about_content' => "About our learning platform.\nMore details.", 'footer_description' => 'Updated footer content.', 'contact_description' => 'Get in touch with our team.'];
        $this->put(route('admin.settings.update'), $payload)->assertRedirect(route('admin', ['page' => 'settings']));
        $this->get(route('home'))->assertSee('<title>Free GK MCQs &amp; Chapter Notes | My Learning Site</title>', false)->assertSee('content="Updated default SEO description."', false)->assertSee('A new homepage heading')->assertSee('Our new introduction.')->assertSee('Updated footer content.')->assertSee('mailto:support@example.com', false);
        $this->get('/about')->assertSee('About My Learning Site')->assertSee('About our learning platform.');
        $this->get('/contact')->assertSee('Get in touch with our team.')->assertSee('support@example.com');
        $this->get(route('daily'))->assertSee('Daily MCQ – My Learning Site');
        $this->get('/missing')->assertNotFound()->assertSee('My Learning Site');
        $this->get('/admin/settings')->assertSee('value="My Learning Site"', false)->assertSee('Our new introduction.');
        $chapter = Chapter::factory()->create(['meta_title' => 'Chapter SEO title', 'meta_description' => 'Chapter SEO description']);
        Quiz::factory()->for($chapter)->create();
        $this->get(route('learn', ['subject' => $chapter->subject->slug, 'chapter' => 0]))->assertSee('<title>Chapter SEO title</title>', false)->assertSee('content="Chapter SEO description"', false)->assertSee('My Learning Site');
    }

    public function test_existing_quiz_wording_is_displayed_as_mcq_with_safe_html_encoding(): void
    {
        DB::table('site_settings')->where('id', 1)->update(['values' => json_encode(['site_description' => 'Free quiz practice & daily quizzes.', 'home_description' => 'Practice quizzes with answers.', 'footer_description' => 'Daily quiz practice.']), 'updated_at' => now()]);
        $this->get(route('home'))->assertOk()
            ->assertSee('<title>Free GK MCQs &amp; Chapter Notes | questionyear</title>', false)
            ->assertSee('content="Free MCQ practice &amp; daily MCQs."', false)
            ->assertSee('Practice MCQs with answers.')
            ->assertSee('Daily MCQ practice.')
            ->assertSee('href="'.route('daily').'"', false);
        $chapter = Chapter::factory()->create(['meta_title' => 'Quiz & <script>alert(1)</script>', 'meta_description' => 'Quiz notes & explanations.']);
        $this->get($chapter->readingUrl(0))->assertOk()
            ->assertSee('<title>MCQ &amp; &lt;script&gt;alert(1)&lt;/script&gt;</title>', false)
            ->assertDontSee('<title>MCQ & <script>', false);
    }

    public function test_logo_can_be_uploaded_replaced_removed_and_served_without_a_storage_symlink(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->put(route('admin.settings.update'), [...$this->payload(), 'logo' => UploadedFile::fake()->image('logo.png', 120, 120)])->assertRedirect();
        $settings = SiteSettings::values();
        $originalLogo = $settings['logo_path'];
        Storage::disk('local')->assertExists($originalLogo);
        $url = SiteSettings::logoUrl($settings);
        $this->get(route('home'))->assertSee($url, false);
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->put(route('admin.settings.update'), [...$this->payload(), 'logo_path' => '../private-file'])->assertRedirect();
        $this->assertSame($originalLogo, SiteSettings::values()['logo_path']);
        $this->put(route('admin.settings.update'), [...$this->payload(), 'logo' => UploadedFile::fake()->image('replacement.jpg', 200, 120)])->assertRedirect();
        $replacementLogo = SiteSettings::values()['logo_path'];
        $this->assertNotSame($originalLogo, $replacementLogo);
        Storage::disk('local')->assertMissing($originalLogo);
        Storage::disk('local')->assertExists($replacementLogo);
        $this->get($url)->assertNotFound();
        $this->put(route('admin.settings.update'), [...$this->payload(), 'remove_logo' => '1'])->assertRedirect();
        $this->assertNull(SiteSettings::values()['logo_path']);
        Storage::disk('local')->assertMissing($replacementLogo);
        $this->get(route('home'))->assertOk()->assertDontSee('class="site-logo-image"', false);
        $this->get('/site-logo/config.php')->assertNotFound();
    }

    public function test_invalid_settings_or_unsafe_logo_files_do_not_change_saved_settings(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->put(route('admin.settings.update'), [...$this->payload(), 'site_title' => '', 'contact_email' => 'invalid'])->assertSessionHasErrors(['site_title', 'contact_email']);
        $this->put(route('admin.settings.update'), [...$this->payload(), 'logo' => UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml')])->assertSessionHasErrors('logo');
        $this->put(route('admin.settings.update'), [...$this->payload(), 'logo' => UploadedFile::fake()->image('too-large.png')->size(2049)])->assertSessionHasErrors('logo');
        $this->assertSame('questionyear', SiteSettings::values()['site_title']);
        $this->assertNull(SiteSettings::values()['logo_path']);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_favicon_can_be_uploaded_replaced_and_removed_across_all_pages(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/admin/settings')->assertOk()->assertSee('name="favicon"', false);
        $this->put(route('admin.settings.update'), [...$this->payload(), 'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32)])->assertRedirect();
        $settings = SiteSettings::values();
        $originalFavicon = $settings['favicon_path'];
        $url = SiteSettings::faviconUrl($settings);
        Storage::disk('local')->assertExists($originalFavicon);
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
        foreach (['/', '/about', '/daily-quiz', '/upcoming', '/missing-page', '/admin/settings'] as $page) {
            $this->get($page)->assertSee('<link rel="icon" href="'.$url.'">', false);
        }
        $this->put(route('admin.settings.update'), [...$this->payload(), 'favicon_path' => '../private-file'])->assertRedirect();
        $this->assertSame($originalFavicon, SiteSettings::values()['favicon_path']);
        $this->put(route('admin.settings.update'), [...$this->payload(), 'favicon' => UploadedFile::fake()->image('replacement.png', 64, 64)])->assertRedirect();
        $replacementFavicon = SiteSettings::values()['favicon_path'];
        $this->assertNotSame($originalFavicon, $replacementFavicon);
        Storage::disk('local')->assertMissing($originalFavicon);
        Storage::disk('local')->assertExists($replacementFavicon);
        $this->get($url)->assertNotFound();
        $this->put(route('admin.settings.update'), [...$this->payload(), 'remove_favicon' => '1'])->assertRedirect();
        $this->assertNull(SiteSettings::values()['favicon_path']);
        Storage::disk('local')->assertMissing($replacementFavicon);
        $this->get(route('home'))->assertDontSee('<link rel="icon"', false);
        $this->get('/site-favicon/config.php')->assertNotFound();
    }

    public function test_unsafe_and_oversized_favicons_are_rejected(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->put(route('admin.settings.update'), [...$this->payload(), 'favicon' => UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml')])->assertSessionHasErrors('favicon');
        $this->put(route('admin.settings.update'), [...$this->payload(), 'favicon' => UploadedFile::fake()->image('too-large.png')->size(1025)])->assertSessionHasErrors('favicon');
        $this->assertNull(SiteSettings::values()['favicon_path']);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    /** @return array<string, string> */
    private function payload(): array
    {
        $settings = SiteSettings::values();
        unset($settings['logo_path'], $settings['favicon_path']);

        return $settings;
    }
}
