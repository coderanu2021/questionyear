<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $seoTitle }}</title><meta name="description" content="{{ $seoDescription }}">@if($seoKeywords)<meta name="keywords" content="{{ $seoKeywords }}">@endif
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;1,400&family=Playfair+Display:wght@700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="{{ asset("css/website.css") }}"><meta name="csrf-token" content="{{ csrf_token() }}">
@include('website.partials.favicon')
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


<script>window.CHAPTER_SEO_TITLE={{ Illuminate\Support\Js::from($chapterMetaTitle) }};window.CURRICULUM={{ Illuminate\Support\Js::from($curriculum) }};window.CURRENT_USER={{ Illuminate\Support\Js::from($currentUser) }};window.QUIZ_IDS={{ Illuminate\Support\Js::from($quizIds) }};</script><script src="{{ asset("js/website.js") }}"></script></body></html>
