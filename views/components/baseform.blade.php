<!-- form inputs. loop thu and include diffrent peices as spesified in the formInputs table.
 form[
    formid = mainForm
    endpoint
    formAction
    method
    submit slot
    --nameid board
    inputs [
            input = "text" "textarea"
            id
            name 
            properties = 'cols="48" rows="4" maxlength="2048"'
        ]
    [
        'input' => 'button', 
        'submit' => 'true', 
        'lable'=> 'name',
        'properties' => 'name="name" autocomplete="off" ......'
     ],
] -->


<div style="text-align: center;" id="mainForm">
    <form id="{{ $data['formID'] }}" action="{{ WEBPATH . $data['endpoint'] }}" method="{{ $data['method'] }}" enctype="multipart/form-data">
        <input type="hidden" name="action" value="{{ $data['formAction'] }}">
        <input type="hidden" name="nameID" value="{{ $data['nameID'] }}">
        <table>
            @foreach ($data['inputs'] as $idata)
                @if ($idata['input'] === 'text' || $idata['input'] === 'file' || $idata['input'] === 'checkbox' || $idata['input'] === 'text')
                    <x-form.wrapper :data="$idata">
                        <x-form.input :data="$idata" :formid="$data['formID']" />
                        @if (isset($idata['submit']))
                            <button type="submit" form="{{ $formID }}">{{ $idata['submit'] }}</button>
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
</div>