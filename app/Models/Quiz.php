<?php

namespace App\Models;

use Database\Factories\QuizFactory;
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
        return ['questions' => 'array'];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function publicUrl(): string
    {
        return route('quiz.show', ['subject' => $this->chapter->subject->slug, 'quiz' => $this->id, 'slug' => Str::slug($this->title) ?: 'practice-quiz']);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }
}
