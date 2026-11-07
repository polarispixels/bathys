@extends('docs.layout')

@section('content')
    {{-- Safe to print unescaped: MarkdownRenderer strips raw HTML from the source. --}}
    {!! $html !!}

    <style>
        .badge { display: inline-block; border-radius: 0.25rem; padding: 0 0.4rem; font-size: 0.75rem; font-weight: 600; }
        .badge-done { background: #dcfce7; color: #166534; }
        .badge-next { background: #dbeafe; color: #1e40af; }
        .badge-planned { background: #e5e5e5; color: #404040; }
        .dark .badge-done { background: #14532d; color: #bbf7d0; }
        .dark .badge-next { background: #1e3a8a; color: #bfdbfe; }
        .dark .badge-planned { background: #404040; color: #e5e5e5; }
    </style>

    <h2 id="where-things-stand">Where things stand</h2>

    @if ($release)
        <p>
            <a href="/docs/changelog">Version {{ $release->version }}, released {{ \Illuminate\Support\Carbon::parse($release->date)->format('F j, Y') }}</a>
        </p>
        @if ($release->summary !== '')
            {{-- Safe to print unescaped: renderInline strips raw HTML from the source. --}}
            <p>{!! $renderer->renderInline($release->summary) !!}</p>
        @endif
    @else
        <p>See the <a href="/docs/roadmap">roadmap</a>.</p>
    @endif

    @forelse ($board as $phase)
        <h3>{{ $phase->title }}</h3>
        <table>
            <thead>
                <tr><th>Step</th><th>Item</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach ($phase->rows as $row)
                    <tr>
                        <td>{{ $row->step }}</td>
                        {{-- Safe to print unescaped: renderInline strips raw HTML from the source. --}}
                        <td>{!! $renderer->renderInline($row->item) !!}</td>
                        <td>
                            @if ($row->badge)
                                <span class="badge badge-{{ $row->badge }}">{{ $row->status }}</span>
                            @else
                                {{ $row->status }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p>See the <a href="/docs/roadmap">roadmap</a>.</p>
    @endforelse
@endsection
