<x-basepage :style="$style" :iconpack="$iconpack" :title="$title">
    <x-title :title="$boardTitle" :subtitle="$subtitle" />
    <x-nav :navLeft="$navLeft" :navRight="$navRight" />
    <!--< x-page-buttons /> -->
    <x-baseform :data="$form" />
    <x-board.thread-listing :threads="$threads" />
    <!--< x-page /> -->
</x-basepage>

<!--pages later
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

<div class="pages">
    [&nbsp;<b>1</b>&nbsp;]</div>
</body>
-->