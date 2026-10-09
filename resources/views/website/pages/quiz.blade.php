<main id="quiz" @if(!$serverQuiz) hidden @endif>
  <div @class(['wrap', 'qwrap' => !$questionAnswerQuiz, 'ca-reader' => $questionAnswerQuiz])><div class="crumb" id="qc"></div><div @class(['box qbox' => !$questionAnswerQuiz]) id="qb">
    @if($serverQuiz && !$questionAnswerQuiz)
      <h1>{{ $serverQuiz->title }}</h1><p>Practice {{ count($serverQuiz->questions) }} multiple-choice questions. Start the interactive quiz to check your answers and explanations.</p>
      @foreach($serverQuiz->questions as $question)<section class="daily-review-item"><h2>{{ $loop->iteration }}. {{ $question['q'] }}</h2><ol type="A">@foreach($question['o'] as $option)<li>{{ $option }}</li>@endforeach</ol></section>@endforeach
    @elseif($questionAnswerQuiz)
      <div class="ca-layout">
        <div class="ca-content">
          <div class="ca-heading">
            <div class="ca-eyebrow"><span>{{ $questionAnswerQuiz->learningSubject()->name }}</span>@if($questionAnswerQuiz->quiz_date)<span class="ca-date"><i class="fa-regular fa-calendar" aria-hidden="true"></i><time datetime="{{ $questionAnswerQuiz->quiz_date->toDateString() }}">{{ $questionAnswerQuiz->quiz_date->format('d M Y') }}</time></span>@endif</div>
            <h1>{{ $questionAnswerQuiz->title }}</h1>
            <p>Daily questions and answers, in one place.</p>
          </div>
          <div class="ca-tools">
            <label class="ca-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input id="ca-search" type="search" placeholder="Find a question or answer" aria-label="Search questions and answers" autocomplete="off"></label>
            <span id="ca-result-count" role="status" aria-live="polite">{{ count($questionAnswerQuiz->questions) }} {{ count($questionAnswerQuiz->questions) === 1 ? 'question' : 'questions' }}</span>
          </div>
          <div class="ca-conversation">
            @foreach($questionAnswerQuiz->questionAnswers() as $question)
              <article class="ca-exchange" id="ca-question-{{ $loop->iteration }}" data-ca-question="{{ $loop->iteration }}">
                <div class="ca-question-message"><span class="ca-question-number">Question {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h2>{{ $question['q'] }}</h2></div>
                <div class="ca-answer-message"><div><span class="ca-answer-label">Answer</span><p>{{ $question['answer'] }}</p></div></div>
              </article>
            @endforeach
          </div>
          <div class="ca-no-results" id="ca-no-results" hidden><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><h2>No matching questions</h2><p>Try another word, or clear your search.</p></div>
          <div class="ca-end"><span>You're all caught up.</span><a href="{{ route('subject', $questionAnswerQuiz->learningSubject()->slug) }}">Browse more {{ $questionAnswerQuiz->learningSubject()->name }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
        </div>
      </div>
    @endif
  </div></div>
</main>
