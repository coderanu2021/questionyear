<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
@include('website.partials.canonical')
@if(request()->routeIs('login', 'register'))<meta name="robots" content="noindex,follow">@endif
@if(request()->routeIs('subject') && !$subjectHasContent)<meta name="robots" content="noindex,follow">@endif
@include('website.partials.seo', ['defaultTitle' => $seoTitle, 'defaultDescription' => $seoDescription, 'defaultKeywords' => $seoKeywords, 'preservePageTitle' => true])
@if(request()->routeIs('home'))
@php
    $homeSchema = [
        ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $siteSettings['site_title'], 'url' => route('home')],
        ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $siteSettings['site_title'], 'url' => route('home')],
    ];
    $homeFaqs = [
        ['question' => 'Is '.$siteSettings['site_title'].' free to use?', 'answer' => 'Yes. Daily MCQs and subject practice are free. You can create an account to save scores and streaks.'],
        ['question' => 'How can I practise for SSC or UPSC?', 'answer' => 'Choose an exam from Practice by exam, then use the available subject notes, topic-wise MCQs and mock tests to revise.'],
        ['question' => 'Do I get explanations for answers?', 'answer' => 'Yes. Practice questions show the correct answer and an explanation after you submit your response.'],
        ['question' => 'Can I practise one topic at a time?', 'answer' => 'Yes. Choose a subject and open a topic-wise set, or use a mixed set when you want to practise across topics.'],
        ['question' => 'Can I use questionyear on my phone?', 'answer' => 'Yes. The site works in a mobile browser, so you can practise without installing an app.'],
    ];
    $homeSchema[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn (array $faq): array => ['@type' => 'Question', 'name' => $faq['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']]], $homeFaqs)];
@endphp
<script type="application/ld+json">{!! json_encode($homeSchema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;1,400&family=Playfair+Display:wght@700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/website.css') }}?v={{ filemtime(public_path('css/website.css')) }}"><meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('css/government-jobs.css') }}?v={{ filemtime(public_path('css/government-jobs.css')) }}">
@include('website.partials.favicon')
@if($serverChapter?->content_verified_at)
@php
    $schemaBaseUrl = rtrim(config('app.url'), '/');
    $schemaUrl = in_array(parse_url($schemaBaseUrl, PHP_URL_HOST), ['localhost', '127.0.0.1', '::1', null], true) ? request()->url() : $schemaBaseUrl.request()->getPathInfo();
    $learningSchema = ['@context' => 'https://schema.org', '@type' => 'LearningResource', 'name' => $serverChapter->title, 'description' => $serverChapter->meta_description, 'inLanguage' => 'en-IN', 'learningResourceType' => 'Study guide', 'educationalUse' => 'Study and revision', 'dateModified' => $serverChapter->updated_at->toIso8601String(), 'url' => $schemaUrl, 'citation' => array_column($serverChapter->content_evidence['sources'] ?? [], 'url')];
    $verifiedFaqs = $serverChapter->content_evidence['faqs'] ?? [];
    $faqSchema = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($faq) => ['@type' => 'Question', 'name' => $faq['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']]], $verifiedFaqs)];
@endphp
<script type="application/ld+json">{!! json_encode(empty($verifiedFaqs) ? [$learningSchema] : [$learningSchema, $faqSchema], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@if($serverQuiz)
@php
    $quizSchema = ['@context' => 'https://schema.org', '@type' => 'Quiz', 'name' => $serverQuiz->title, 'url' => $serverQuiz->publicUrl(), 'numberOfQuestions' => count($serverQuiz->questions), 'educationalLevel' => $serverQuiz->learningSubject()->name];
@endphp
<script type="application/ld+json">{!! json_encode($quizSchema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@php
    $pageBreadcrumbs = [['name' => 'Home', 'url' => route('home')]];
@endphp
@if(!request()->routeIs('home'))
@php
    if ($serverChapter) {
        $pageBreadcrumbs[] = ['name' => $serverChapter->subject->name, 'url' => route('subject', $serverChapter->subject->slug)];
        $pageBreadcrumbs[] = ['name' => $serverChapter->title, 'url' => request()->url()];
    } elseif ($serverQuiz) {
        $pageBreadcrumbs[] = ['name' => $serverQuiz->learningSubject()->name, 'url' => route('subject', $serverQuiz->learningSubject()->slug)];
        $pageBreadcrumbs[] = ['name' => $serverQuiz->title, 'url' => request()->url()];
    } elseif ($selectedSubject) {
        $pageBreadcrumbs[] = ['name' => 'Subjects', 'url' => request()->url().'#subjects'];
        $pageBreadcrumbs[] = ['name' => $selectedSubject->name, 'url' => request()->url()];
    } elseif (request()->routeIs('login', 'register')) {
        $pageBreadcrumbs[] = ['name' => request()->routeIs('login') ? 'Log in' : 'Register', 'url' => request()->url()];
    }
@endphp
@include('website.partials.breadcrumb-schema', ['items' => $pageBreadcrumbs])
@endif
</head>
<body>
@include('website.partials.header')

@include('website.pages.home')

@include('website.pages.subj')

@include('website.pages.learn')

@include('website.pages.auth')

@include('website.pages.quiz')

@include('website.partials.footer')
@include('website.partials.content-protection')
<script>window.GUEST_QUESTIONS_USED={{ (int) session('guest_questions_used', 0) }};</script>
@include('website.partials.learning-scripts')


<script>window.CHAPTER_SEO_TITLE={{ Illuminate\Support\Js::from($chapterMetaTitle) }};window.CURRICULUM={{ Illuminate\Support\Js::from($curriculum) }};window.CURRENT_USER={{ Illuminate\Support\Js::from($currentUser) }};window.QUIZ_IDS={{ Illuminate\Support\Js::from($quizIds) }};</script><script src="{{ asset('js/website.js') }}?v={{ filemtime(public_path('js/website.js')) }}"></script></body></html>
