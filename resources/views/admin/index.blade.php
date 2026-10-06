<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $siteSettings['site_title'] }} Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<link rel="stylesheet" href="{{ asset("css/admin.css") }}"><meta name="csrf-token" content="{{ csrf_token() }}">
@include('website.partials.favicon')
</head>
<body>
<aside class="side" id="side">
  <div class="logo">@include('website.partials.brand-logo'){{ $siteSettings['site_title'] }}</div>
  <nav class="nav" id="nav"></nav>
  <div class="me"><div class="av">AD</div><div><b>Admin</b><span>Super admin</span></div></div>
</aside>
<div class="main">
  <header class="top">
    <button class="burger" id="burger" aria-label="Menu"><svg class="ic" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
    <h1 id="title">Dashboard</h1><div class="grow"></div><a class="btn" href="{{ route('learning.reports') }}">Question reports</a>
    <div class="search hide"><svg class="ic" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input placeholder="Search anything..." id="gs"></div>
    <a class="btn" href="/">View website</a><form method="POST" action="/account/logout">@csrf<button class="btn">Logout</button></form>
  </header>
  <main class="page" id="page">@if($page === 'settings')@include('admin.pages.settings')@endif</main>
</div>
<div class="modal" id="modal"><div class="dlg" id="dlg"></div></div>
<div class="toast" id="toast"></div>


<script>window.ADMIN_STATE={{ Illuminate\Support\Js::from($state) }};window.ADMIN_PAGE={{ Illuminate\Support\Js::from($page) }};</script>@if($page !== 'settings')@include('admin.pages.' . $page)@endif
<script>window.CHAPTER_PAGE={{ Illuminate\Support\Js::from($chapterPage ?? null) }};</script>
<script src="{{ asset("js/admin.js") }}?v={{ filemtime(public_path('js/admin.js')) }}"></script></body></html>
