<{!! $data['input'] !!} type="text" form="{{ $formid }}" {{ !empty($data['id']) ? 'id=' . $data['id'] : '' }} name="{{ $data['name'] ?? '' }}" {!! $data['properties'] ?? '' !!}>{!! $slot !!}</{!!$data['input']!!}>
@if (isset($data['inline']))
    {!! $data['inline'] !!}
@endif