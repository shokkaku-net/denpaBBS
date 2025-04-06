[<label>
    <input type="checkbox" form="{{ $formid }}" {{ !empty($data['id']) ? 'id=' . $data['id'] : '' }} name="{{ $data['name'] ?? '' }}" value="{{ $data['value'] }}">
    {{ $data['lable'] }}
</label>]