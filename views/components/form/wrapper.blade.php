<tr>
    <td class="accent">
        <label for="{{ $data['id'] ?? 'input' }}">
            {{ $data['lable'] ?? 'Input' }}
        </label>
    </td>
    <td>
        {!! $slot !!}
    </td>
</tr>