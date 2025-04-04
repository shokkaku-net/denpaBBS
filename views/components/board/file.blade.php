<div class="files">
    @foreach ($files as $file)
        <div class="fileName">
            [<a href="{{ $file['weblocation'] }}" download="{{ $file['name'] }}">
                download
            </a>]
            <small>{{ $file['size'] }}</small>
            <a href="{{ $file['weblocation'] }}" target="_blank" rel="nofollow">
                {{ $file['name'] }}
            </a>
        </div>
    @endforeach
    @foreach ($files as $file)
        <div class="file ">
            <a href="{{ $file['weblocation'] }}" class="image" target="_blank" rel="nofollow">
                <img class=" float media" src="{{ $file['webthumblocation'] }}" loading="lazy" title="{{ $file['name'] }}">
            </a>
        </div>
    @endforeach
</div>