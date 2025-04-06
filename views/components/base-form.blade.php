<form class="mainForm" id="{{ $data['formID'] }}" action="{{ $data['endpoint'] }}" method="{{ $data['method'] }}"
    enctype="multipart/form-data">
    <input type="hidden" name="action" value="{{ $data['formAction'] }}" form="{{ $data['formID'] }}">
    <input type="hidden" name="nameID" value="{{ $data['nameID'] }}" form="{{ $data['formID'] }}">
    @if (isset($data['hidden']))
        @foreach ($data['hidden'] as $hd)
            <input type="hidden" form="{{ $data['formID'] }}" name="{{ $hd['name']  }}" value="{{ $hd['value'] }}">
        @endforeach
    @endif
    <table>
        @foreach ($data['inputs'] as $idata)
            @if ($idata['input'] === 'text' || $idata['input'] === 'file' || $idata['input'] === 'checkbox' || $idata['input'] == 'password')
                <x-form.wrapper :data="$idata">
                    <x-form.input :data="$idata" :formid="$data['formID']" />
                    @if (isset($idata['submit']))
                        <button type="submit" form="{{ $data['formID'] }}">{{ $idata['submit'] }}</button>
                    @endif
                </x-form.wrapper>

            @elseif ($idata['input'] === 'textarea' || $idata['input'] === 'button')
                <x-form.wrapper :data="$idata">
                    <x-form.input-sloted :data="$idata" :formid="$data['formID']">
                        {{ $idata['data'] }}
                    </x-form.input-sloted>
                </x-form.wrapper>
            @endif

        @endforeach
    </table>

    @if (isset($submitRules))
        <details class="submitRules">
            <summary>submition rules</summary>
            @foreach ($data['submitRules'] as $rule)
                <li>{{ $rule }}</li>
            @endforeach
        </details>
    @endif
</form>