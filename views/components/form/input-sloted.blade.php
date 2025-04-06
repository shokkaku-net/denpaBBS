<{!! $data['input'] !!} type="text" form="{{ $formid }}" id="{{ $data['id'] ?? 'input' }}" name="{{ $data['name'] ?? '' }}" {!! $data['properties'] ?? '' !!}>{!! $slot !!}</{!!$data['input']!!}>
@if (isset($data['inline']))
    {!! $data['inline'] !!}
@endif