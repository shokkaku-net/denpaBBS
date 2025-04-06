@foreach ($boardbuttons as $button)
    @if ($button['type'] == "hyperbutton")
        <x-hyperbutton :name="$button['name']" :endpoint="$button['endpoint']" :action="$button['action']" />
    @elseif ($button['type'] == "button")
        <x-button :name="$button['name']" :location="$button['location']" />
    @endif
@endforeach