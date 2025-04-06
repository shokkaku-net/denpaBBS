<select form="{{ $formid }}" {{ !empty($data['id']) ? 'id=' . $data['id'] : '' }} name="{{ $data['name'] ?? '' }}" >
    @foreach ( $data['options'] as $option)
        <option value="{{ $option['value'] }}" {{ (isset($option['selected']) ? 'selected' : '' }} >{{ $option['value'] }}</option>
    @endforeach
</select>