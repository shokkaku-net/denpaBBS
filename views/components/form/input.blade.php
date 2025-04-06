<input type="{{ $data['input'] }}" form="{{ $formid }}" id="{{ $data['id'] ?? 'input' }}" name="{{ $data['name'] ?? '' }}" {!! $data['properties'] ?? '' !!}>
@if (isset($data['inline']))
    {!! $data['inline'] !!}
@endif