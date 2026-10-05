<?php

namespace App\Models;

use Database\Factories\ChapterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Chapter extends Model
{
    /** @use HasFactory<ChapterFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['notes' => 'array'];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function readingUrl(int $index): string
    {
        if ($this->subject->slug === 'history' && in_array($this->category, ['Ancient History', 'Medieval History', 'Modern History'], true)) {
            return route('chapter', ['category' => Str::slug($this->category), 'chapterSlug' => Str::slug($this->title)]);
        }

        return route('learn', ['subject' => $this->subject->slug, 'chapter' => $index]);
    }
}
