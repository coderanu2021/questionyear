<?php

namespace App;

use Illuminate\Support\Facades\DB;

class SiteSettings
{
    /** @return array{site_title: string, site_description: string, contact_email: string, home_title: string, home_description: string, footer_description: string, about_content: string, contact_description: string, logo_path: ?string, favicon_path: ?string} */
    public static function values(): array
    {
        $stored = DB::table('site_settings')->where('id', 1)->value('values');

        $settings = array_replace([
            'site_title' => 'questionyear',
            'site_description' => 'Free subject-wise MCQ practice, chapter notes and daily MCQs for students and exam aspirants.',
            'contact_email' => 'questionyear2026@gmail.com',
            'home_title' => 'Practice any subject. Know where you stand.',
            'home_description' => 'Chapter MCQs in history, geography, science and more. Get instant answers with explanations, and track your progress topic by topic.',
            'footer_description' => 'Free subject-wise MCQ practice for students and exam aspirants. Learn, test and improve every day.',
            'about_content' => 'Chapter notes and practice MCQs together. Choose a subject, study the available notes and practice with immediate feedback.',
            'contact_description' => 'Send a question, report an issue or suggest a correction.',
            'logo_path' => null,
            'favicon_path' => null,
        ], $stored ? json_decode($stored, true, flags: JSON_THROW_ON_ERROR) : []);

        foreach (['site_description', 'home_title', 'home_description', 'footer_description', 'about_content'] as $field) {
            $settings[$field] = WebsiteText::mcq($settings[$field]);
        }

        return $settings;
    }

    /** @param array{logo_path: ?string} $settings */
    public static function logoUrl(array $settings): ?string
    {
        return $settings['logo_path'] ? route('site.logo', ['filename' => basename($settings['logo_path'])]) : null;
    }

    /** @param array{favicon_path: ?string} $settings */
    public static function faviconUrl(array $settings): ?string
    {
        return $settings['favicon_path'] ? route('site.favicon', ['filename' => basename($settings['favicon_path'])]) : null;
    }
}
