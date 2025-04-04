@foreach ($threads as $thread)
    <div id="{{ 't' . $thread['id'] }}" class="thread">
        @foreach ($thread['posts'] as $post)
            <x-board.post :data="$post" :thread="$thread" />
        @endforeach
    </div>
@endforeach