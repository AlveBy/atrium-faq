{{-- Questions with their answers: the answer opens under the question. --}}
<div class="faq">
    @foreach ($questions as $question)
        <details class="faq-question" id="faq-{{ $question->id }}">
            <summary>{{ $question->translated('question') }}</summary>
            <div class="content">{!! $question->translated('answer') !!}</div>
        </details>
    @endforeach
</div>
