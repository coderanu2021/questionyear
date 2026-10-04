@if(auth()->user()?->role !== 'admin')
<script src="{{ asset('js/content-protection.js') }}?v={{ filemtime(public_path('js/content-protection.js')) }}" data-protect-copy="{{ request()->routeIs('quiz') ? 'true' : 'false' }}"></script>
@endif
