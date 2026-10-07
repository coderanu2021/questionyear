<?php

namespace App\Models;

use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['questions' => 'array', 'quiz_date' => 'date'];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function publicUrl(): string
    {
        return route('quiz.show', ['subject' => $this->learningSubject()->slug, 'quiz' => $this->id, 'slug' => Str::slug($this->title) ?: 'practice-quiz']);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function learningSubject(): Subject
    {
        return $this->subject ?? $this->chapter->subject;
    }

    public function isPublished(): bool
    {
        return $this->subject_id !== null
            ? $this->subject->slug === 'current-affairs' && $this->status === 'published'
            : $this->chapter?->status === 'published' && ($this->learningSubject()->slug !== 'current-affairs' || $this->status === 'published');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->where(fn (Builder $query) => $query->whereNull('subject_id')->whereHas('chapter', fn (Builder $chapter) => $chapter->where('status', 'published'))->where(fn (Builder $query) => $query->where('status', 'published')->orWhereHas('chapter.subject', fn (Builder $subject) => $subject->where('slug', '!=', 'current-affairs'))))
                ->orWhere(fn (Builder $query) => $query->whereNull('chapter_id')->where('status', 'published')->whereHas('subject', fn (Builder $subject) => $subject->where('slug', 'current-affairs')));
        });
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }
}
