{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{--
    The FAQ page: every category with its questions, drawn inside the
    theme's frame. The theme passes the categories as `records`; the
    questions and the FAQPage structured data come from FaqServiceProvider.
--}}
@extends('atrium-theme::layout', ['metaTitle' => $title])

@push('head')
    {{ $jsonLd }}
@endpush

@section('content')
    <h1>{{ $title }}</h1>

    @forelse ($groups as $group)
        <section class="faq-category">
            <h2><a href="{{ $group['category']->url() }}">{{ $group['category']->title() }}</a></h2>
            @include('atrium-faq::partials.questions', ['questions' => $group['questions']])
        </section>
    @empty
        <p class="empty">{{ __('atrium-theme::theme.empty') }}</p>
    @endforelse

    @if ($records !== null)
        @include('atrium-theme::partials.pagination', ['paginator' => $records])
    @endif
@endsection
