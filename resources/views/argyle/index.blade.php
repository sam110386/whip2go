<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Argyle (stub)</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem; max-width: 48rem; }
        code { background: #f4f4f4; padding: 0.15rem 0.35rem; }
    </style>
</head>
<body>
    <h1>Argyle — Laravel stub</h1>
    <p>Minimal layout for migration. Legacy Argyle JS is not wired here.</p>
    <ul>
        <li><strong>userid</strong> (encoded): <code>{{ $userid }}</code></li>
        <li><strong>token</strong>: {{ $token !== '' ? '(set)' : '(empty)' }}</li>
        <li><strong>uberlyftPartners</strong>: {{ json_encode($uberlyftPartners) }}</li>
        <li><strong>incomedataPartners</strong>: {{ json_encode($incomedataPartners) }}</li>
    </ul>
</body>
</html>
