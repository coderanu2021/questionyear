<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('website.partials.seo', ['defaultTitle' => $title.' – '.$siteSettings['site_title'], 'defaultDescription' => $description, 'socialCards' => true])
    <link rel="stylesheet" href="{{ asset('css/website.css') }}?v={{ filemtime(public_path('css/website.css')) }}">
    @include('website.partials.favicon')
    @include('website.partials.canonical', ['socialCards' => true])
    @php
        $noticeBreadcrumbs = [['name' => 'Home', 'url' => route('home')], ['name' => 'Notifications', 'url' => route('notifications.index')], ...($exam ? [['name' => $exam === 'railways' ? 'Railways' : $notices[$exam]['title'], 'url' => request()->url()]] : [])];
    @endphp
    @include('website.partials.breadcrumb-schema', ['items' => $noticeBreadcrumbs])
</head>
<body>
@include('website.partials.header')
@if(auth()->check())<script>window.CURRENT_USER={{ Illuminate\Support\Js::from(auth()->user()->only('name', 'email')) }};</script>@endif
<main class="wrap notification-page">
    <div class="crumb"><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('notifications.index') }}">Notifications</a>@if($exam)<span>/</span><b>{{ $exam === 'railways' ? 'Railways' : $notices[$exam]['title'] }}</b>@endif</div>
    <span class="daily-label">QUESTIONYEAR · GOVERNMENT JOBS</span>
    <h1>{{ $title }}</h1>
    <p class="sub">{{ $description }}</p>
    @if($exam === null)
        <p>Browse all available recruitment guides below. Open a guide for its dates, eligibility and official sources. Check the status on each card: a prior-year reference does not confirm a new recruitment cycle.</p>
        <div class="jobs-filters" role="group" aria-label="Government job categories">
            @foreach($categories as $filter => $label)
                <a href="{{ $filter === 'all' ? route('jobs.index') : route('jobs.category', ['category' => $filter]) }}" @if($category === $filter) aria-current="page" @endif>{{ $filter === 'all' ? 'All Jobs' : $label }}</a>
            @endforeach
        </div>
    @endif
    @if($exam && isset($jobDetails[$exam]['verified']))
        <p class="notification-checked">Source check: <time datetime="{{ $jobDetails[$exam]['verified'] }}">{{ date('j F Y', strtotime($jobDetails[$exam]['verified'])) }}</time>. {{ $jobDetails[$exam]['verification'] }}</p>
    @else
        <p class="notification-checked">Existing guide references last checked: <time datetime="2026-10-14">14 October 2026</time>. New entries show their own source-check date and limitations. Please check the official notification for the latest updates.</p>
    @endif
    <div class="notification-layout">
        <div class="notification-content">
            @if($exam === null || $exam === 'railways')
                <div class="notification-list">
                    @foreach($notices as $slug => $notice)
                        <article class="card notification-list-card">
                            <div class="notification-badges"><span>{{ strtoupper($jobDetails[$slug]['category']) }}</span><span>{{ $notice['status'] }}</span></div>
                            <h2><a href="{{ route('notifications.show', ['exam' => $slug]) }}">{{ $notice['title'] }}</a></h2>
                            <p>{{ $jobDetails[$slug]['organization'] }} · {{ isset($jobDetails[$slug]['type']) ? 'Exam-stage update' : ($jobDetails[$slug]['qualification'] === 'all' ? 'Post-specific qualification' : $categories[$jobDetails[$slug]['qualification']]) }}</p>
                            <dl class="job-facts"><div><dt>{{ isset($jobDetails[$slug]['type']) ? 'Update type' : 'Vacancies' }}</dt><dd>{{ isset($jobDetails[$slug]['type']) ? $categories[$jobDetails[$slug]['type']] : $jobDetails[$slug]['vacancies'] }}</dd></div><div><dt>{{ isset($jobDetails[$slug]['type']) ? 'Notice reference' : 'Last date' }}</dt><dd>{{ $jobDetails[$slug]['deadline'] }}</dd></div></dl>
                            <p>{{ $notice['summary'] }}</p>
                            @if(isset($jobDetails[$slug]['verified']))<p class="notification-source">Source checked: {{ date('j F Y', strtotime($jobDetails[$slug]['verified'])) }}. {{ $jobDetails[$slug]['verification'] }}</p>@endif
                            <div class="job-card-links"><a class="btn btn-o" href="{{ route('notifications.show', ['exam' => $slug]) }}">View notification →</a><a href="{{ $jobDetails[$slug]['source'] }}" target="_blank" rel="noopener noreferrer">Check official source ↗</a></div>
                        </article>
                    @endforeach
                    @if(count($notices) === 0)
                        <div class="box jobs-empty"><h2>{{ $title }} updates</h2><p>No verified {{ $category === 'admit-cards' ? 'admit card notices' : ($category === 'results' ? 'result notices' : 'recruitment guides') }} are listed in this section yet.</p><p>Use the official websites below to check new announcements. A missing listing here does not mean an announcement has not been released.</p><a class="btn btn-o" href="{{ route('jobs.index') }}">Browse all jobs</a></div>
                    @endif
                </div>
                <section class="box jobs-sources" aria-labelledby="official-job-sources">
                    <h2 id="official-job-sources">Official recruitment websites</h2>
                    <p>Use these websites to find notifications, application links, admit cards and results. Check the notice number before following any update.</p>
                    <div class="notification-table"><table><thead><tr><th scope="col">Official website</th><th scope="col">Recruitment category</th></tr></thead><tbody>
                        <tr><td><a href="https://ssc.gov.in/" target="_blank" rel="noopener noreferrer">SSC ↗</a></td><td>GD, CGL, CHSL, MTS</td></tr>
                        <tr><td><a href="https://www.upsc.gov.in/" target="_blank" rel="noopener noreferrer">UPSC ↗</a></td><td>Civil Services, NDA, CDS</td></tr>
                        <tr><td><a href="https://www.rrbcdg.gov.in/" target="_blank" rel="noopener noreferrer">RRB Chandigarh ↗</a></td><td>Railway NTPC, ALP, Technician and other RRB notices</td></tr>
                        <tr><td><a href="https://ncs.gov.in/" target="_blank" rel="noopener noreferrer">National Career Service ↗</a></td><td>Government and other job listings</td></tr>
                        <tr><td><a href="https://ibps.in/" target="_blank" rel="noopener noreferrer">IBPS ↗</a></td><td>PO, clerical and Regional Rural Bank recruitment</td></tr>
                    </tbody></table></div>
                </section>
            @elseif(isset($jobDetails[$exam]['details']))
                <article class="box notification-article">
                    <div class="notification-badges"><span>{{ strtoupper($jobDetails[$exam]['category']) }}</span><span>{{ $notices[$exam]['status'] }}</span></div>
                    <p>{{ $notices[$exam]['summary'] }}</p>
                    @if(isset($jobDetails[$exam]['overview']))
                        <div class="notification-table"><table><caption>Recruitment details: CEN 07/2026</caption><thead><tr><th scope="col">Details</th><th scope="col">Information</th></tr></thead><tbody>
                            @foreach($jobDetails[$exam]['overview'] as $label => $value)
                                <tr><th scope="row">{{ $label }}</th><td>{{ $value }}</td></tr>
                            @endforeach
                        </tbody></table></div>
                    @endif
                    @foreach($jobDetails[$exam]['details'] as $heading => $paragraph)
                        <section><h2>{{ $heading }}</h2><p>{{ $paragraph }}</p></section>
                    @endforeach
                    <section><h2>Important official links</h2><p><a href="{{ $jobDetails[$exam]['source'] }}" target="_blank" rel="noopener noreferrer">Read the official source ↗</a></p><p class="notification-source">Source checked: {{ date('j F Y', strtotime($jobDetails[$exam]['verified'])) }}. {{ $jobDetails[$exam]['verification'] }}</p></section>
                    <section><h2>Preparation and next steps</h2><p>Follow the official syllabus for your examination. Practise regularly and review mistakes before increasing speed.</p><a class="btn btn-o" href="{{ route('daily') }}">Practise daily MCQs</a></section>
                </article>
            @elseif($exam === 'upsc')
                @include('website.partials.notification-upsc')
            @else
                @include('website.partials.notification-railways')
            @endif
            @if($exam !== null && $exam !== 'railways' && ! isset($jobDetails[$exam]['details']))
                <section class="box notification-article" aria-labelledby="application-checklist">
                    <h2 id="application-checklist">Application details and checklist</h2>
                    <p>The guide above identifies its recruitment cycle and sources. Fields marked below still need checking against the full official notification; do not use an older notice as confirmation of new rules.</p>
                    <div class="notification-table"><table><tbody>
                        <tr><th scope="row">Organization</th><td>{{ $jobDetails[$exam]['organization'] }}</td></tr>
                        <tr><th scope="row">Total vacancies</th><td>{{ $jobDetails[$exam]['vacancies'] }}. Use the official post-wise and category-wise table.</td></tr>
                        <tr><th scope="row">Educational qualification</th><td>{{ $categories[$jobDetails[$exam]['qualification']] }}: see the guide above for the applicable cycle and additional conditions.</td></tr>
                        <tr><th scope="row">Age limit and relaxation</th><td>See the cycle-specific reference above. Check the age cut-off and category conditions in the full notice.</td></tr>
                        <tr><th scope="row">Salary and pay scale</th><td>See the salary section below for the pay reference, applicable cycle and official sources.</td></tr>
                        <tr><th scope="row">Application dates</th><td>See the dated source above. Prior-year dates do not apply to a fresh cycle.</td></tr>
                        <tr><th scope="row">Application fee</th><td>Category-wise fees, exemptions and refund conditions have not been verified in this guide.</td></tr>
                        <tr><th scope="row">Selection process</th><td>Use the full notice for every stage and any post-specific skill, physical or medical requirements.</td></tr>
                        <tr><th scope="row">Exam pattern and syllabus</th><td>Marks, timing, negative marking and the complete syllabus have not been verified in this guide. Read the official exam scheme before planning.</td></tr>
                    </tbody></table></div>
                    <h3>How to apply online</h3>
                    <ol><li>Open the official recruitment website and find the exact notice number.</li><li>Read the detailed notification and any corrections. Confirm that applications are open.</li><li>Follow the application link in that notice, register and enter your details.</li><li>Upload the required documents in the specified format and pay any applicable fee.</li><li>Check the preview, submit before the deadline and save the confirmation.</li></ol>
                    <h3>Required documents</h3>
                    <p>Prepare age proof, education certificates, a recent photograph, signature and any applicable category or disability certificate. The notice determines the accepted documents, formats and deadlines.</p>
                    <h3>Important official links</h3>
                    <p><a href="{{ $jobDetails[$exam]['source'] }}" target="_blank" rel="noopener noreferrer">Official source for this guide ↗</a>. Notification PDF links, where available, appear beside the relevant reference above. Use the issuing authority's website to find the current application, admit card and result links.</p>
                    <h3>Preparation tips</h3>
                    <p>Start with the official syllabus. Set a daily revision target, practise timed questions and keep a list of mistakes. QuestionYear quizzes support revision; check their topics against your exam syllabus.</p>
                    <div class="notification-actions"><a class="btn btn-o" href="{{ route('daily') }}">Daily practice</a><a class="btn btn-l" href="{{ route('subject', ['subject' => 'mathematics']) }}">Maths quizzes</a></div>
                    <h3>Admit cards and results</h3>
                    <p>No admit card or result release is confirmed by this guide. <a href="{{ route('jobs.category', ['category' => 'admit-cards']) }}">Browse admit card updates</a> or <a href="{{ route('jobs.category', ['category' => 'results']) }}">browse result updates</a>, then check the official recruitment website.</p>
                </section>
            @endif
            @if($exam !== null && $exam !== 'railways')
                <section class="box notification-article" aria-labelledby="notification-salary">
                    <h2 id="notification-salary">{{ $exam === 'canara-bank-graduate-apprentices-2026' ? 'Salary and stipend' : 'Salary and pay scale' }}</h2>
                    <p>{{ $salaryGuides[$exam]['summary'] }}</p>
                    <p>{{ $salaryGuides[$exam]['note'] }}</p>
                    <p>Basic pay is the fixed pay component. Gross salary includes applicable allowances. In-hand salary is the amount left after deductions. These amounts should not be treated as interchangeable.</p>
                    <ul class="notification-source">
                        @foreach($salaryGuides[$exam]['sources'] as $sourceLabel => $sourceUrl)
                            <li><a href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer">{{ $sourceLabel }}</a></li>
                        @endforeach
                    </ul>
                    @if($exam === 'rrb-ntpc-undergraduate-cen-07-2026')
                        <p class="notification-source">Pay reference checked on <time datetime="2026-10-16">16 October 2026</time> using the notification mirror linked above. The issuing RRB website copy has not been independently verified.</p>
                    @else
                        <p class="notification-source">Pay references checked on <time datetime="2026-10-15">15 October 2026</time>. Some references were available through the official document's search index. Prior-cycle references are labelled above; check later amendments before relying on pay figures.</p>
                    @endif
                </section>
                <section class="box notification-article" aria-labelledby="notification-faqs">
                    <h2 id="notification-faqs">Frequently asked questions</h2>
                    @foreach($faqs[$exam] as $question => $answer)
                        <h3>{{ $question }}</h3>
                        <p>{{ $answer }}</p>
                    @endforeach
                </section>
            @endif
        </div>
        <aside class="box notification-sidebar">
            <h2>Before you apply</h2>
            @if($exam)<a href="{{ route('notifications.index') }}">← All notifications</a>@endif
            <p>Start with the notice number. Then check the post, dates and rules. A calendar gives a plan. The detailed notice gives the full terms.</p>
            <ol><li>Check your date of birth.</li><li>Match your course to the post.</li><li>Read the rules for your category.</li><li>Check skill and medical tests.</li><li>Keep clear copies of your documents.</li></ol>
            <p>A degree alone does not prove you can apply. Age, documents and post rules matter too.</p>
            <a class="btn btn-o" href="{{ route('daily') }}">Practise daily MCQs</a>
            <a href="{{ route('subject', ['subject' => 'history']) }}">Revise History</a>
            <a href="{{ route('subject', ['subject' => 'mathematics']) }}">Practise Maths</a>
        </aside>
    </div>
</main>
@include('website.partials.footer')
<script src="{{ asset('js/navigation.js') }}"></script>
</body>
</html>
