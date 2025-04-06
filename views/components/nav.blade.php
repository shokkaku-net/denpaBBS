@if (!empty($navLeft) || !empty($navRight))
    <nav class="navBar">
        @if (!empty($navLeft))
            <span class="navLeft">
                @foreach ($navLeft as $grouping)
                    <span class="nowrap">[
                        @php
                            $count = count($grouping);
                            $i = 0;
                        @endphp
                        @foreach ($grouping as $entry)
                            @if (is_array($entry) && isset($entry['name'], $entry['url']))
                                <a class="navLink" href="{{ $entry['url'] }}">{{ $entry['name'] }}</a>
                                @if (++$i < $count)
                                    /
                                @endif
                            @endif
                        @endforeach
                        ]</span>
                @endforeach
            </span>
        @endif

        @if (!empty($navRight))
            <span class="navRight">
                @foreach ($navRight as $grouping)
                    <span class="nowrap">[
                        @php
                            $count = count($grouping);
                            $i = 0;
                        @endphp
                        @foreach ($grouping as $entry)
                            @if (is_array($entry) && isset($entry['name'], $entry['url']))
                                <a class="navLink" href="{{ $entry['url'] }}">{{ $entry['name'] }}</a>
                                @if (++$i < $count)
                                    /
                                @endif
                            @endif
                        @endforeach
                        ]</span>
                @endforeach
            </span>
        @endif
    </nav>
@endif