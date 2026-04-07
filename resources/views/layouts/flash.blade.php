<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $pageTitle ?? ($title_for_layout ?? 'Flash') }}</title>
    @if(!empty($pause) && !empty($url))
        <meta http-equiv="refresh" content="{{ $pause }};url={{ $url }}">
    @endif
    <style>
        p { text-align:center; font:bold 1.1em sans-serif; }
        a { color:#444; text-decoration:none; }
        a:hover { text-decoration: underline; color:#44E; }
    </style>
</head>
<body>
<p><a href="{{ $url ?? '#' }}">{{ $message ?? 'Continue' }}</a></p>
</body>
</html>
