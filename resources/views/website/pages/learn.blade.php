<main id="learn" @if(!$serverChapter) hidden @endif>
@if($serverChapter && $serverChapterData)
<div class="bk-hero"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">{{ __('Home') }}</a><span>/</span><a href="{{ route('subject', $serverChapter->subject->slug) }}">{{ $serverChapter->subject->name }}</a><span>/</span><b>{{ $serverChapter->title }}</b></div><h1>{{ $serverChapter->title }}</h1>@if($serverChapterData['sub'] ?? null)<p class="bk-sub">{{ $serverChapterData['sub'] }}</p>@endif</div></div>
<div class="wrap bk-lay"><article class="bk-art">
@foreach($serverChapterData['s'] ?? [] as $section)
<section class="bk-s"><div class="bk-h"><h2>{{ $section['h'] }}</h2></div>
@foreach($section['p'] ?? [] as $paragraph)
@php($paragraph = preg_replace('/(<\/?)(h1)(\b)/i', '$1h2$3', $paragraph))
@if(preg_match('/<\/?(?:p|h[1-6]|ul|ol|table|div|section|figure|blockquote)\b/i', $paragraph)){!! $paragraph !!}@else<p>{!! $paragraph !!}</p>@endif
@endforeach
@if(isset($section['t']))<div class="bk-t"><table><thead><tr>@foreach($section['t']['h'] as $heading)<th scope="col">{{ $heading }}</th>@endforeach</tr></thead><tbody>@foreach($section['t']['r'] as $row)<tr>@foreach($row as $cell)<td>{!! $cell !!}</td>@endforeach</tr>@endforeach</tbody></table></div>@endif
@if(isset($section['n']))<blockquote>{{ $section['n']['x'] }}</blockquote>@endif
</section>
@endforeach
@if($serverChapterData['sum'] ?? [])<section class="bk-sum"><h2>{{ __('Chapter summary') }}</h2><ul>@foreach($serverChapterData['sum'] as $summary)<li>{!! $summary !!}</li>@endforeach</ul></section>@endif
@foreach($serverChapter->quizzes as $quiz)<p><a class="btn btn-o" href="{{ $quiz->publicUrl() }}">Practice {{ App\WebsiteText::mcq($quiz->title) }}</a></p>@endforeach
</article></div>
@endif
</main>
