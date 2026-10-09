<?php

namespace App;

class WebsiteText
{
    public static function mcq(string $text): string
    {
        return preg_replace_callback('/\bquiz(?:zes)?\b/i', fn (array $match): string => strtolower($match[0]) === 'quizzes' ? 'MCQs' : 'MCQ', $text) ?? $text;
    }
}
