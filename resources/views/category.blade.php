{{-- One category of the FAQ with its questions, inside the theme's frame. --}}
@extends('atrium-theme::layout', [
    'metaTitle' => $page->seoTitle(),
    'metaDescription' => $page->seoDescription(),
])

@push('head')
    {{ $jsonLd }}
@endpush

@section('content')
    <h1>{{ $page->title() }}</h1>

    @if ($questions->isNotEmpty())
        @include('atrium-faq::partials.questions', ['questions' => $questions])
    @else
        <p class="empty">{{ __('atrium-theme::theme.empty') }}</p>
    @endif
@endsection
