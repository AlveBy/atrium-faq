{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{-- Questions with their answers: the answer opens under the question. --}}
<div class="faq">
    @foreach ($questions as $question)
        <details class="faq-question" id="faq-{{ $question->id }}">
            <summary>{{ $question->translated('question') }}</summary>
            <div class="content">{!! $question->translated('answer') !!}</div>
        </details>
    @endforeach
</div>
