{{-- Cake `app/View/Layouts/error.ctp` (minimal). Prefer Laravel's `errors::*` for HTTP errors when wiring handlers. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Error')</title>
</head>
<body>
@yield('content')
</body>
</html>
