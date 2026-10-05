New {{ $submission->type === 'feedback' ? 'feedback' : 'contact message' }} received on QuestionYear.

Name: {!! $submission->name !!}
Email: {!! $submission->email !!}
@if($submission->type === 'feedback')
Rating: {{ $submission->rating }} / 5
@endif

Message:
{!! $submission->message !!}
