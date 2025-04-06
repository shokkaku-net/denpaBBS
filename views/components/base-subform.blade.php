<form class="subform" id="{{ $data['formID'] }}" action="{{ $data['endpoint'] }}" method="{{ $data['method'] }}">
    <input type="hidden" name="action" value="{{ $data['formAction'] }}" form="{{ $data['formID'] }}">
    <input type="hidden" name="nameID" value="{{ $data['nameID'] }}" form="{{ $data['formID'] }}">
    @if (isset($data['hidden']))
        @foreach ($data['hidden'] as $hd)
            <input type="hidden" form="{{ $data['formID'] }}" name="{{ $hd['name']  }}" value="{{ $hd['value'] }}">
        @endforeach
    @endif

    @foreach ($data['inputs'] as $idata)
        @if($idata['input'] == 'lable')
            <span>{{ $idata['lable'] }}:</span>

        @elseif($idata['input'] == 'br')
            <br>

        @elseif($idata['input'] == 'dropdown')
            <x-form.dropdown :formid="$data['formID']" :data="$idata" />

        @elseif($idata['input'] == 'checkbox')
            <x-form.sub-checkbox :formid="$data['formID']" :data="$idata" />

        @elseif ($idata['input'] === 'text' || $idata['input'] == 'password')
            <x-form.input :data="$idata" :formid="$data['formID']" />
            @if (isset($idata['submit']))
                <button type="submit" form="{{ $data['formID'] }}">{{ $idata['submit'] }}</button>
            @endif
        @endif
    @endforeach

</form>