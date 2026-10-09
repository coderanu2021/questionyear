<div class="head"><div><h2>Import Word file</h2><p>Upload a .docx file to save notes or questions directly to the database.</p></div></div>
@if(session('status'))
<div class="card" role="status" style="padding:20px;margin-bottom:20px"><p>{{ session('status') }}</p><a class="btn pri" href="{{ session('imported_url') }}">Review imported content</a></div>
@endif
@if($errors->any())
<div class="card" role="alert" style="padding:20px;margin-bottom:20px">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach<p>Select the file again to retry. Nothing was imported.</p></div>
@endif
<div class="card" style="padding:24px;max-width:850px">
<form method="POST" action="{{ route('admin.import.store') }}" enctype="multipart/form-data" id="word-import-form">
@csrf
<div class="field"><label for="import-mode">Content type</label><select name="mode" id="import-mode" required>
@foreach(['notes' => 'Chapter notes / article', 'mcq' => 'Multiple choice questions', 'qa' => 'Questions and answers'] as $value => $label)
<option value="{{ $value }}" @selected(old('mode', 'notes') === $value)>{{ $label }}</option>
@endforeach
</select></div>
<div class="field"><label for="import-title">Chapter or quiz title</label><input id="import-title" name="title" value="{{ old('title') }}" maxlength="255" required></div>
<div class="field" data-import-mode="notes"><label for="import-subject">Subject</label><select id="import-subject" name="subject_id"><option value="">Select subject</option>@foreach($importSubjects as $subject)<option value="{{ $subject->id }}" @selected((string) old('subject_id') === (string) $subject->id)>{{ $subject->name }}</option>@endforeach</select></div>
<div class="field" data-import-mode="notes"><label for="import-category">History category (optional)</label><select id="import-category" name="category"><option value="">No category</option>@foreach(['Ancient History', 'Medieval History', 'Modern History'] as $category)<option @selected(old('category') === $category)>{{ $category }}</option>@endforeach</select></div>
<div class="field" data-import-mode="mcq"><label for="import-chapter">Chapter</label><select id="import-chapter" name="chapter_id"><option value="">Select chapter</option>@foreach($state['chapters'] as $chapter)@if(!in_array($chapter['subject'], ['Current Affairs', 'General Knowledge']))<option value="{{ $chapter['id'] }}" @selected((string) old('chapter_id') === (string) $chapter['id'])>{{ $chapter['subject'] }} — {{ $chapter['title'] }} ({{ $chapter['status'] }})</option>@endif @endforeach</select><p>MCQs are visible immediately if the selected chapter is published. Choose a draft chapter to review privately.</p></div>
<div class="field" data-import-mode="qa"><label for="import-qa-subject">Question-answer subject</label><select id="import-qa-subject" name="qa_subject"><option value="general-knowledge" @selected(old('qa_subject') === 'general-knowledge')>General Knowledge</option><option value="current-affairs" @selected(old('qa_subject') === 'current-affairs')>Current Affairs</option></select></div>
<div class="field"><label for="import-file">Word document</label><input id="import-file" type="file" name="file" accept=".docx" required><p>Maximum 10 MB. Save old .doc files as .docx in Word first. Text, headings and tables are imported; images and exact Word styling are not imported.</p></div>
<div data-import-mode="mcq"><p>Number each question and put each option and answer on its own line. Maximum 200 questions. Answers are required.</p><pre>1. What is 2 + 2?
A. 1
B. 2
C. 3
D. 4
Answer: D
Explanation: Two plus two is four.</pre></div>
<div data-import-mode="qa"><p>Number each question and include an Answer: line.</p><pre>1. Where is Harappa located?
Answer: Punjab, Pakistan.</pre></div>
<button type="submit" class="btn pri" id="word-import-submit">Upload and save</button>
<p id="word-import-progress" role="status" hidden>Reading and saving your Word document…</p>
</form>
</div>
<script>
(() => {
 const form = document.getElementById('word-import-form');
 const mode = document.getElementById('import-mode');
 const update = () => form.querySelectorAll('[data-import-mode]').forEach(section => {
   section.hidden = section.dataset.importMode !== mode.value;
   section.querySelectorAll('select').forEach(select => { select.disabled = section.hidden; select.required = !section.hidden && select.name !== 'category'; });
 });
 mode.addEventListener('change', update);
 update();
 form.addEventListener('submit', () => { document.getElementById('word-import-submit').disabled = true; document.getElementById('word-import-progress').hidden = false; });
})();
</script>
