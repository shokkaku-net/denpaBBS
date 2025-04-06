<!DOCTYPE html>
<html lang="en-US">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <!--always get newest content-->
    <meta http-equiv="cache-control" content="max-age=0">
    <meta http-equiv="cache-control" content="no-cache">
    <meta http-equiv="expires" content="0">
    <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT">
    <meta http-equiv="pragma" content="no-cache">
    <!--mobile view-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--tell bots its ok to scrape the whole site. disallowing this wont stop bots FYI-->
    <meta name="robots" content="follow,archive">
    <!--board specific stuff-->
    <title>{{ $pagetitle }}</title>
    <link rel="stylesheet" type="text/css" href="/static/css/base.css">
    <link rel="stylesheet" type="text/css" href="/static/css/boardStyles/{{ $style }}.css" title="boardcss">
    <link rel="shortcut icon" href="/static/image/iconPacks/{{ $iconpack }}/favicon.png">
    <script src="/static/js/system/onClickEmbedFile.js" defer></script>
    <script src="/static/js/system/postidToForm.js" defer></script>
    <script src="/static/js/system/autoFillCookies.js" defer></script>
    <script src="/static/js/system/highlight.js" defer></script>
</head>

<body id="top">
    {!! $slot !!}
    <div id="bottom" class="footer">- you are running <a rel="nofollow noreferrer license"
            href="https://github.com/shokkaku-net/denpaBBS" target="_blank">DenpaBBS</a>. a clear and easy to read
        imageboard software -</div>
</body>