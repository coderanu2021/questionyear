<main id="quiz" @if(!$currentAffairsQuiz) hidden @endif>
  <div class="wrap qwrap"><div class="crumb" id="qc"></div><div class="box qbox" id="qb">
    @if($currentAffairsQuiz)
      <h1>{{ $currentAffairsQuiz->title }}</h1>
      @if($currentAffairsQuiz->quiz_date)<p><time datetime="{{ $currentAffairsQuiz->quiz_date->toDateString() }}">{{ $currentAffairsQuiz->quiz_date->format('d M Y') }}</time></p>@endif
      <div class="rv">
        @foreach($currentAffairsQuiz->questionAnswers() as $question)
          <article><h2 class="qt">{{ $loop->iteration }}. {{ $question['q'] }}</h2><p style="white-space:pre-wrap"><strong>Answer:</strong> {{ $question['answer'] }}</p></article>
        @endforeach
      </div>
      <a class="btn btn-l" href="{{ route('subject', 'current-affairs') }}">Back to Current Affairs</a>
    @endif
  </div></div>
</main>
