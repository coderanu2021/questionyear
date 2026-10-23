<?php

namespace App\Models;

use App\PostContent;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $fillable = ['type', 'title', 'slug', 'excerpt', 'content', 'status', 'published_at', 'featured_image_url', 'featured_image_alt', 'author_name', 'official_source_url'];

    public function thumbnailUrl(): ?string
    {
        if (filled($this->featured_image_url)) {
            return $this->featured_image_url;
        }

        $html = PostContent::render($this->content ?? '');
        if (preg_match('/<img\b[^>]*\bsrc="([^"]+)"/i', $html, $matches)) {
            return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return null;
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil(count(preg_split('/\s+/u', trim(strip_tags($this->content ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: []) / 200));
    }

    public function previewText(): string
    {
        return Str::limit($this->excerpt ?: html_entity_decode(strip_tags($this->content ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 160);
    }

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }
}
