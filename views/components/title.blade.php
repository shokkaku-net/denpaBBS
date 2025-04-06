<div class="boardTitle">
    @if (!empty($data['logo']))
        <img class="logo" src="{{ $data['logo'] }}" \>
    @endif
    <h1 class="title">
        {!! $data['title'] !!}
    </h1>
    <h5 class="subtitle">
        {!! $data['subtitle'] !!}
    </h5>
</div>