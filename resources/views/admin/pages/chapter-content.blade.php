<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Chapter content review · {{ $siteSettings['site_title'] }}</title><meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
<link rel="stylesheet" href="{{ asset('css/chapter-content-admin.css') }}">
@include('website.partials.favicon')
</head>
<body class="chapter-content-admin">
<main class="page">
<div class="head"><div><h1>Chapter content</h1><p>Prepare, review and verify comprehensive chapter revisions.</p></div><a class="btn" href="{{ route('admin', ['page' => 'chapters']) }}">Back to chapters</a></div>
@if(session('status'))<p role="status" class="card content-panel">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="card content-panel"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(!$revision)
<section class="card content-panel"><h2>Prepare a batch</h2>
<p>Start with three chapters; each batch accepts up to five existing chapter IDs. Supply a separate syllabus and source dossier for each chapter. Nothing is published automatically.</p>
<p>Read the actual NCERT textbook, government publication or academic source first. Record its exact HTTPS page URL, edition/date and page or section locator, and relevant source excerpts. A URL alone is not evidence. Include Introduction and Basic concepts in the syllabus, followed by every relevant topic. Optionally set review_after_days (1–365); use a short interval for changing facts. The default is one day for Current Affairs and 180 days for other subjects. Source excerpts are private review material.</p>
<form method="POST" action="{{ route('admin.content.store') }}">@csrf
<label for="batch">Batch brief JSON (array of chapter objects; replace the sample fields)</label>
<textarea id="batch" name="batch" rows="20" required spellcheck="false">{{ old('batch', json_encode([['chapter_id' => $chapters->first()?->id, 'size' => 'small', 'syllabus' => ['Introduction', 'Basic concepts', 'Replace with actual syllabus topics'], 'sources' => [['id' => 'source-1', 'title' => '', 'url' => '', 'locator' => '', 'excerpt' => '']]]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
<label for="mode">Operation</label><select id="mode" name="mode"><option value="generate">Queue generation from sources</option><option value="import" @selected(old('mode') === 'import')>Import complete editorial drafts (include payload on each object)</option></select>
<p>Sizes: small 1,500–2,500 words; medium 3,000–5,000; large 5,000–8,000+. Word counts refer to explanations, excluding MCQs and FAQs. Review completeness; never pad.</p>
<button class="btn pri">Prepare batch</button>
</form>
<details><summary>Draft JSON format for import</summary><p>Each imported object needs the brief above plus a payload object with: seo_title, meta_description, slug, sections [{heading, html, source_ids}], exam_facts [strings], misconceptions [strings], summary [strings], questions [{q, o: [four choices], c: zero-based answer index, explanation, source_ids}], faqs [{q, a}], uncertainties [strings]. Every approved syllabus heading must appear exactly once as a section. HTML uses paragraphs, H3 headings, lists and tables. Include at least 20 MCQs and 5–10 FAQs. All imported content still requires review and verification.</p></details>
<p>Worker command: <code>php artisan queue:work chapter-content --queue=chapter-content --timeout=150 --tries=3</code>. Keep this worker supervised on the server. Refresh to see progress.</p>
</section>
<section class="card content-panel"><h2>Working revisions and history</h2><div class="tw"><table><thead><tr><th>Revision / batch</th><th>Chapter</th><th>Status</th><th>Progress</th><th></th></tr></thead><tbody>
@forelse($revisions as $entry)<tr><td>#{{ $entry->id }}<small>{{ $entry->batch_id }}</small></td><td>{{ $chapters->firstWhere('id', $entry->chapter_id)?->title }}</td><td>{{ $entry->status }}</td><td>{{ $entry->generation_state }}</td><td><a class="btn" href="{{ route('admin.content.show', $entry->id) }}">Review</a></td></tr>@empty<tr><td colspan="5">No revisions yet.</td></tr>@endforelse
</tbody></table></div>{{ $revisions->links() }}</section>
<section class="card content-panel"><h2>Subject-wise inventory</h2><p>These are review flags, not automatic declarations that existing content is incorrect. CLI export: <code>php artisan chapters:audit --json</code>.</p>
@foreach($chapters->groupBy('subject.name') as $subjectName => $entries)<h3>{{ $subjectName }} ({{ $entries->count() }})</h3><div class="tw"><table><thead><tr><th>ID / chapter</th><th>Status</th><th>Words</th><th>Review flags</th></tr></thead><tbody>@foreach($entries as $entry)<tr><td>#{{ $entry->id }} {{ $entry->title }}</td><td>{{ $entry->status }}</td><td>{{ $audits[$entry->id]['words'] }}</td><td>{{ implode('; ', $audits[$entry->id]['issues']) ?: 'No automated flags; continue periodic review.' }}</td></tr>@endforeach</tbody></table></div>@endforeach
</section>
@else
<p><a href="{{ route('admin.content.index') }}">All batches and chapter inventory</a></p>
<section class="card content-panel"><h2>{{ $chapter->subject->name }} · {{ $chapter->title }}</h2><p>Revision #{{ $revision['id'] }} · {{ $revision['status'] }} · {{ $revision['generation_state'] }} · version {{ $revision['version'] }}</p>
<p>{{ $revision['payload']['_completed'] ?? 0 }} / {{ count($revision['brief']['syllabus']) + 3 }} generation steps saved. Existing public content stays available until an approved replacement is published.</p>
@if($revision['last_error'])<p role="alert">{{ $revision['last_error'] }}</p>@endif
@if($revision['status'] === 'draft')<form method="POST" action="{{ route('admin.content.resume', $revision['id']) }}">@csrf<input type="hidden" name="version" value="{{ $revision['version'] }}"><button class="btn">Resume failed or stalled generation</button><p>Stalled jobs can be resumed after ten minutes. Completed sections are reused.</p></form>@endif
<details><summary>Approved syllabus and source dossier</summary><pre>{{ json_encode($revision['brief'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details>
<h3>Quality checks</h3><p>{{ $revision['checks']['words'] ?? 0 }} explanatory words. Checks are heuristics: they do not prove factual accuracy, full coverage or originality.</p>
@foreach($revision['checks']['errors'] ?? [] as $issue)<p role="alert">Blocked: {{ $issue }}</p>@endforeach
@foreach($revision['checks']['warnings'] ?? [] as $issue)<p>Review: {{ $issue }}</p>@endforeach
@if($revision['payload']['uncertainties'] ?? [])<h3>Claims requiring verification</h3><ul>@foreach($revision['payload']['uncertainties'] as $uncertainty)<li>{{ $uncertainty }}</li>@endforeach</ul>@endif
@if($revision['verified_at'])<p>Verified {{ $revision['verified_at'] }} by admin #{{ $revision['verified_by'] }}. {{ $revision['review_note'] }}</p>@endif
@if($revision['status'] === 'published' && ($chapter->content_evidence['revision_id'] ?? null) === $revision['id'] && ($chapter->content_evidence['review_status'] ?? null) === 'pending')<p role="status">Published with factual review pending. No human verification is recorded. {{ $chapter->content_evidence['publication_authorization']['note'] ?? '' }}</p>@endif
@if(in_array($revision['status'], ['draft', 'review', 'verified']))<form method="POST" action="{{ route('admin.content.archive', $revision['id']) }}">@csrf<input type="hidden" name="version" value="{{ $revision['version'] }}"><button class="btn">Archive working revision</button><p>Retain this draft and its original snapshot, then start a new brief if sources or the live chapter have changed.</p></form>@endif
</section>
<section class="card content-panel"><h2>Preview</h2><article class="chapter-preview">{!! $preview !!}</article></section>
@if(in_array($revision['status'], ['draft', 'review', 'verified']) && in_array($revision['generation_state'], ['complete', 'failed']))
<section class="card content-panel"><h2>Edit revision</h2><p>Saving resets verification. Use the structured JSON to edit explanations, questions, answers and source IDs. You can correct the syllabus and expand the source dossier here.</p><form method="POST" action="{{ route('admin.content.update', $revision['id']) }}">@csrf @method('PUT')<input type="hidden" name="version" value="{{ $revision['version'] }}"><label for="brief">Syllabus and source dossier JSON</label><textarea id="brief" name="brief" rows="12" required spellcheck="false">{{ old('brief', json_encode($revision['brief'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea><label for="payload">Draft JSON</label><textarea id="payload" name="payload" rows="28" required spellcheck="false">{{ old('payload', json_encode($revision['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea><button class="btn pri">Save for review</button></form></section>
@endif
@if($revision['status'] === 'review')
@if($revision['generation_state'] === 'complete' && !$chapter->content_verified_at)
<section class="card content-panel"><h2>Publish with factual review pending</h2><p>This explicit editorial exception makes the chapter and its practice quiz public with a visible pending-review label. It does not record human verification. Structural checks, unresolved-claim checks and replacement protection still apply. After human review, prepare and verify a new revision to replace this version.</p><form method="POST" action="{{ route('admin.content.publish', $revision['id']) }}">@csrf<input type="hidden" name="version" value="{{ $revision['version'] }}"><label class="attestation"><input type="checkbox" name="publish_pending_review" value="1" required>I authorise publication now with factual review pending.</label>@if($chapter->status === 'published')<label class="attestation"><input type="checkbox" name="approve_replacement" value="1" required>I approve replacing this published chapter. Its original snapshot is retained.</label>@endif<label for="publication-note">Publication authorisation and pending review</label><textarea id="publication-note" name="publication_note" rows="4" minlength="30" required>{{ old('publication_note') }}</textarea><button class="btn">Publish with review pending</button></form></section>
@endif
<section class="card content-panel"><h2>Human verification</h2><p>Read the source documents yourself and resolve all uncertain claims. Check every answer and explanation. Review dates, editions and current legal or economic facts. Automatic checks cannot replace this review.</p><form method="POST" action="{{ route('admin.content.verify', $revision['id']) }}">@csrf<input type="hidden" name="version" value="{{ $revision['version'] }}">
@foreach(['facts_checked' => 'I checked all factual claims, dates, definitions, MCQ answers and explanations against the cited source documents and locators.', 'syllabus_checked' => 'I checked complete coverage of the actual chapter syllabus, examples, tables and important terms.', 'originality_checked' => 'I checked originality and near-duplicates against existing chapters and sources; wording is not copied.', 'readability_checked' => 'I reviewed all readability warnings and confirmed the explanations suit students aged 15–20.'] as $field => $label)<label class="attestation"><input type="checkbox" name="{{ $field }}" value="1" required>{{ $label }}</label>@endforeach
<label for="review-note">Review evidence, editions/dates checked, and resolution of warnings</label><textarea id="review-note" name="review_note" rows="5" minlength="30" required>{{ old('review_note') }}</textarea><button class="btn pri">Mark verified</button></form></section>
@endif
@if($revision['status'] === 'verified')
<section class="card content-panel"><h2>Publish verified revision</h2><p>This applies the reviewed content to the existing chapter and creates its practice quiz. Chapter routes and existing quizzes are preserved. The suggested SEO slug is recorded; established URLs are retained.</p><form method="POST" action="{{ route('admin.content.publish', $revision['id']) }}">@csrf<input type="hidden" name="version" value="{{ $revision['version'] }}">@if($chapter->status === 'published')<label class="attestation"><input type="checkbox" name="approve_replacement" value="1" required>I approve replacing this published chapter with this verified revision. Its original content snapshot remains in revision history.</label>@endif<button class="btn pri">Publish verified chapter</button></form></section>
@endif
@endif
</main>
</body></html>
