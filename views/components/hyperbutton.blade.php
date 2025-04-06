<form method="post" action="{{ $endpoint }}" enctype="multipart/form-data">
    <input type="hidden" name="action" value="{{ $action }}">
    [<button type="submit" class="hyperButton">{{ $name }}</button>]
</form>