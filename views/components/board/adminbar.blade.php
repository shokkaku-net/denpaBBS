@foreach ($adminbar as $data)
    <div style="display: flex">
        <span class="adminlist">
            {{ $data['lable'] }} :
        </span>
        <x-board.buttons :boardbuttons="$data['buttons']" />
    </div>
@endforeach