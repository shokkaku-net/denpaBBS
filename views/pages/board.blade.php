<x-base-page>
    <x-nav />
    @if (isset($loggedin))
        <div class=" theading3"><b>Logged in as a: {{ $loggedin}}</b></div>
    @endif
    @if (isset($adminbar))
        <div class="adminbar" style="display: flex; flex-direction: column;">
            <x-board.adminbar />
        </div>
    @endif
    <x-title :data="$title" />
    @if (isset($boardbuttons))
        <div class="boardbuttons" style="display: flex;">
            <x-board.buttons :boardbuttons="$boardbuttons" />
        </div>
    @endif
    @if ($mode == "reply")
        <div class="theading"><b>Posting mode: {{ $mode}}</b></div>
    @endif
    <x-base-form :data="$mainform" />
    <x-board.thread-listing />

    <x-button :location="'#top'" :name="'Top'" />

    @if (isset($paging))
        <x-paging :paging="$paging" />
    @endif
    <x-base-subform :data="$subform" />
</x-base-page>