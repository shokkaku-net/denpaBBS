<header class="boardTitle">
    @if (!empty($data['logo']))
        <img class="logo" src="{{ $data['logo'] }}" \>
    @endif
    <h1 class="title">
        {!! $data['title'] !!}
    </h1>
    <div class="subtitle">
        {!! $data['subtitle'] !!}
    </div>
</header>