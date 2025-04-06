<div class="post {{ isset($data['isOp']) ? "op" : "reply" }}" id="p{{ $data['id'] }}">
    @if (isset($data['files']) && isset($data['isOp']))
        <x-board.file :files="$data['files']" />
    @endif
    <div class="postinfo">
        <input form="postManage" type="checkbox" name="postIDs[]" value="{{ $data['id'] }}">
        <span class="bigger"><b class="subject">{!! $data['subject'] !!}</b></span>
        @if ($data['email'] != "")
            <span class="name"><a href="mailto:{{ $data['email'] }}"><b>{!! $data['name'] !!}</b></a></span>
        @else
            <span class="name"><b>{!! $data['name'] !!}</b></a></span>
        @endif

        <span class="time">{{ $data['time'] }}</span>
        <span class="postnum"><a href="{{ $data['postLocation'] }}#p{{ $data['id'] }}" class="no">No.</a>&nbsp;<a href="{{ $data['postLocation'] }}#formPost" title="Quote">{{ $data['id'] }}</a></span>
        @if (isset($data['isOp']) && $mode == "listing")
            <x-button :name="'Reply'" :location="$data['postLocation']" />
        @endif
    </div>
    @if (isset($data['files']) && !isset($data['isOp']))
        <x-board.file :files="$data['files']" />
    @endif
    <!-- admin buttons if they exist -->
    <blockquote class="comment">{!! $data['comment'] !!}</blockquote>
    @if (isset($thread) && isset($thread['omitedPosts']) && isset($data['isOp']))
        <span class="omittedposts">{{ $thread['omitedPosts'] }} posts omitted. Click Reply to view.</span>
    @endif
</div>