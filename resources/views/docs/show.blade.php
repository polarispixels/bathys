@extends('docs.layout')

@section('content')
    {{-- Safe to print unescaped: MarkdownRenderer strips raw HTML from the source. --}}
    {!! $html !!}
@endsection
