<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $title_for_layout ?? 'Admin')</title>
    @stack('styles')
</head>
<body style="font-family: Arial, Helvetica, sans-serif;">
    <div style="padding: 14px 18px; border-bottom: 1px solid #eee; margin-bottom: 16px;">
        <div style="font-weight: 700;">Admin</div>
        <div style="font-size: 12px; color: #666;">
            @php
                $admin = session('SESSION_ADMIN');
                $name = is_array($admin) ? trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '')) : '';
            @endphp
            Signed in: {{ $name ?: 'unknown' }}
        </div>
    </div>

    <main style="padding: 0 18px;">
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>

