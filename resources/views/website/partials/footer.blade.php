<footer>
<div class="wrap fcols">
  <div><div class="logo" style="color:#fff;margin-bottom:12px">@include('website.partials.brand-logo'){{ $siteSettings['site_title'] }}</div><p style="margin:0;max-width:34ch">{{ __($siteSettings['footer_description']) }}</p><a class="site-contact-email" href="mailto:{{ $siteSettings['contact_email'] }}">{{ $siteSettings['contact_email'] }}</a></div>
  <div><h4>{{ __('Subjects') }}</h4><a href="/#subjects">{{ __('History') }}</a><a href="/#subjects">{{ __('Geography') }}</a><a href="/subject/physics">{{ __('Science') }}</a><a href="/#subjects">{{ __('Mathematics') }}</a><a href="/#subjects">{{ __('Current Affairs') }}</a></div>
  <div><h4>{{ __('Practice') }}</h4><a href="/daily-quiz">{{ __('Daily MCQ') }}</a><a href="{{ route('learning') }}">{{ __('Mock tests') }}</a><a href="{{ route('learning') }}">{{ __('My learning') }}</a><a href="/#subjects">{{ __('Topic-wise sets') }}</a><a href="{{ route('learning.leaderboard') }}">{{ __('Weekly leaderboard') }}</a></div>
  <div><h4>{{ __('Exams') }}</h4><a href="/exams/upsc">UPSC</a><a href="/exams/ssc">SSC</a><a href="/exams/banking">Banking</a><a href="/exams/railways">Railways</a><a href="/exams/neet">NEET and JEE</a></div>
  <div><h4>{{ __('Company') }}</h4><a href="/about">{{ __('About us') }}</a><a href="/contact">{{ __('Contact') }}</a><a href="{{ route('feedback') }}">{{ __('Feedback') }}</a><a href="/help">{{ __('Help centre') }}</a><a href="https://www.instagram.com/questionyear2026/" target="_blank" rel="noopener noreferrer">Instagram · @questionyear2026</a></div>
</div>
<div class="wrap copy"><span>© {{ now()->year }} {{ $siteSettings['site_title'] }}. All rights reserved.</span><span><a href="{{ route('page', ['page' => 'privacy']) }}">{{ __('Privacy Policy') }}</a><a href="{{ route('page', ['page' => 'terms']) }}">{{ __('Terms & Conditions') }}</a><a href="{{ route('page', ['page' => 'cookies']) }}">{{ __('Cookies') }}</a></span></div>
</footer>
