<input type="{{ $data['input'] }}" form="{{ $formid }}"  {{ !empty($data['id']) ? 'id='.$data['id'] : '' }} name="{{ $data['name'] ?? '' }}" {!! $data['properties'] ?? '' !!}>
@if (isset($data['inline']))
    {!! $data['inline'] !!}
@endif