<footer>
<div class="wrap fcols">
  <div><div class="logo" style="color:#fff;margin-bottom:12px">@include('website.partials.brand-logo'){{ $siteSettings['site_title'] }}</div><p style="margin:0;max-width:34ch">{{ $siteSettings['footer_description'] }}</p><a class="site-contact-email" href="mailto:{{ $siteSettings['contact_email'] }}">{{ $siteSettings['contact_email'] }}</a></div>
  <div><h4>Subjects</h4><a href="/#subjects">History</a><a href="/#subjects">Geography</a><a href="/subject/physics">Science</a><a href="/#subjects">Mathematics</a><a href="/#subjects">Current Affairs</a></div>
  <div><h4>Practice</h4><a href="/daily-quiz">Daily quiz</a><a href="{{ route('learning') }}">Mock tests</a><a href="{{ route('learning') }}">My learning</a><a href="/#subjects">Topic-wise sets</a><a href="{{ route('learning.leaderboard') }}">Weekly leaderboard</a></div>
  <div><h4>Exams</h4><a href="/exams/upsc">UPSC</a><a href="/exams/ssc">SSC</a><a href="/exams/banking">Banking</a><a href="/exams/railways">Railways</a><a href="/exams/neet">NEET and JEE</a></div>
  <div><h4>Company</h4><a href="/about">About us</a><a href="/contact">Contact</a><a href="{{ route('feedback') }}">Feedback</a><a href="/help">Help centre</a></div>
</div>
<div class="wrap copy"><span>© {{ now()->year }} {{ $siteSettings['site_title'] }}. All rights reserved.</span><span><a href="/privacy">Privacy</a><a href="/terms">Terms of use</a><a href="/cookies">Cookies</a></span></div>
</footer>