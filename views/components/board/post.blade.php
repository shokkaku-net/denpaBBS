<div class="post {{ isset($data['isOp']) ? "op" : "" }}" id="p{{ $data['id'] }}">
    <div class="postinfo">
        <input form="postManage" type="checkbox" name="postIDs[]" value="{{ $data['id'] }}">
        <span class="bigger"><b class="subject">{!! $data['subject'] !!}</b></span>
        <span class="name"><a href="mailto:dds"><b>{!! $data['name'] !!}</b></a></span>
        <span class="time">{{ $data['time'] }}</span>
        <span class="postnum"><a href="{{ $data['postLocation'] }}#p{{ $data['id'] }}" class="no">No.</a>&nbsp;<a href="{{ $data['postLocation'] }}#formPost" title="Quote">{{ $data['id'] }}</a></span>
    </div>
    @if (isset($data['files']))
        <x-board.file :files="$data['files']" />
    @endif
    <!-- admin buttons if they exist -->
    <blockquote class="comment">{!! $data['comment'] !!}</blockquote>
    @if (isset($thread) && isset($thread['omitedPosts']) && isset($data['isOp']))
        <span class="omittedposts">{{ $thread['omitedPosts'] }} posts omitted. Click Reply to view.</span>
    @endif
</div>