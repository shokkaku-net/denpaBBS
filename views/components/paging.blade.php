<div class="pages">
    @php
        $base = $paging['baseurl'];
    @endphp

    {{-- First page link --}}
    @if (!empty($paging['startPage']) && $paging['curPage'] > $paging['startPage'])
        <a href="{{ $base . $paging['startPage'] }}">&lt;&lt;</a>
    @endif

    {{-- Back link --}}
    @if (!empty($paging['prevPage']))
        [&nbsp;<a href="{{ $base . $paging['prevPage'] }}">back</a>&nbsp;]
    @endif

    {{-- Page numbers --}}
    @for ($i = $paging['startPage']; $i <= $paging['endPage']; $i++)
        @if ($i == $paging['curPage'])
            [&nbsp;<b>{{ $i }}</b>&nbsp;]
        @else
            [&nbsp;<a href="{{ $base . $i }}">{{ $i }}</a>&nbsp;]
        @endif
    @endfor

    {{-- Next link --}}
    @if (!empty($paging['nextPage']))
        [&nbsp;<a href="{{ $base . $paging['nextPage'] }}">next</a>&nbsp;]
    @endif

    {{-- Last page link --}}
    @if (!empty($paging['endPage']) && $paging['curPage'] < $paging['endPage'])
        <a href="{{ $base . $paging['endPage'] }}">&gt;&gt;</a>
    @endif
</div>