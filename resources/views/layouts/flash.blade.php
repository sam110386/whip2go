{{-- Cake `app/View/Layouts/flash.ctp` — pass $pageTitle, $pause, $url, $message from the controller when redirecting. --}}
@php
    $pageTitle = $pageTitle ?? 'Notice';
    $pause = isset($pause) ? (int) $pause : 1;
    $url = $url ?? '/';
    $message = $message ?? 'Continue';
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $pageTitle }}</title>
@if(!config('app.debug'))
    <meta http-equiv="Refresh" content="{{ $pause }};url={{ $url }}" />
@endif
<style>
P { text-align:center; font:bold 1.1em sans-serif }
A { color:#444; text-decoration:none }
A:hover { text-decoration: underline; color:#44E }
</style>
</head>
<body>
<p>
    <a href="{{ $url }}">{{ $message }}</a>
</p>
</body>
</html>
