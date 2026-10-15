<?php

namespace Tests\Feature;

use App\Models\Post;
use Database\Seeders\IdbiBankNewsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdbiBankNewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_is_published_with_banner_sources_and_metadata_without_duplicates(): void
    {
        $this->seed(IdbiBankNewsSeeder::class);
        $this->seed(IdbiBankNewsSeeder::class);

        $post = Post::where('slug', 'idbi-bank-fairfax-stake-sale-2026-explained')->sole();
        $this->assertSame('news', $post->type);
        $this->assertSame('published', $post->status);
        $this->assertSame('/idbi-bank-stake-sale-banner.png', $post->thumbnailUrl());
        $this->get(route('news.index'))->assertOk()->assertSee($post->title);
        $this->get(route('news.show', $post->slug))->assertOk()
            ->assertSee('IDBI Bank Stake Sale: Fairfax Deal Explained Simply')
            ->assertSee('60.72%')->assertSee('does not describe the transaction as already completed')
            ->assertSee('PRID=2323318', false)->assertSee('PR2194_14032019.pdf', false)
            ->assertSee('idbi-bank-stake-sale-banner.png')->assertSee('AI-generated editorial illustration');
        $this->assertDatabaseCount('page_seo', 1);
    }

    public function test_reseeding_preserves_editor_changes(): void
    {
        $this->seed(IdbiBankNewsSeeder::class);
        $post = Post::where('slug', 'idbi-bank-fairfax-stake-sale-2026-explained')->sole();
        $post->update(['content' => '<p>Editor correction.</p>', 'status' => 'draft']);
        $this->seed(IdbiBankNewsSeeder::class);
        $this->assertSame('<p>Editor correction.</p>', $post->fresh()->content);
        $this->assertSame('draft', $post->fresh()->status);
    }
}
