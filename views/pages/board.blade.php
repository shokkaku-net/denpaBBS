<x-basepage>
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
    <x-baseform :data="$mainform" />
    <x-board.thread-listing />

    <!--
        <form name="managePost" id="managePost" action="/<$NAMEID>" method="post">
            <table style="text-align: right;">
                <tr>
                    <td>
                        <input type="hidden" name="action" value="deletePosts">
                        <input type="hidden" name="nameID" value="$NAMEID">

                        Delete Post: [<label><input type="checkbox" name="fileOnly" id="fileOnly" value="on">File
                            only</label>]<br>
                        Password: <input type="password" name="password" size="16" maxlength="8">
                        <input type="submit" value="Submit">
                    </td>
                </tr>
            </table>
        </form>
    -->
    <x-button :location="'#top'" :name="'Top'" />

    @if (isset($paging))
        <x-paging :paging="$paging" />
    @endif
</x-basepage>