<tr>
    <td class="accent">
        <label {{ !empty($data['id']) ? 'for="' . $data['id'] . '"' : '' }}>
            {{ $data['lable'] ?? 'Input' }}
        </label>
    </td>
    <td>
        {!! $slot !!}
    </td>
</tr>