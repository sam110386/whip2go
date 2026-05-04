<?php

use Illuminate\Support\Str;

if (!function_exists('legacy_asset')) {
    /**
     * Prefix a path for Cake webroot assets (theme2, assets, img, …).
     */
    function legacy_asset(string $path): string
    {
        $base = rtrim((string) config('legacy.asset_base', ''), '/');
        $path = ltrim($path, '/');
        if ($base === '') {
            return '/' . $path;
        }

        return $base . '/' . $path;
    }
}

if (!function_exists('legacy_site_url')) {
    /**
     * Global SITE_URL for legacy JS (matches Cake `SITE_URL` constant intent).
     */
    function legacy_site_url(): string
    {
        $configured = trim((string) config('legacy.site_url', ''));
        $url = ($configured !== '') ? $configured : url('/');
        return Str::finish((string) $url, '/');
    }
}

if (!function_exists('legacy_path_normalize')) {
    /**
     * Strip leading slashes for comparing request path to menu module_url paths.
     */
    function legacy_path_normalize(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }
}

if (!function_exists('legacy_admin_menu_href')) {
    /**
     * `admin_modules.module_url` values are stored as `/admin/...`.
     * Cloud sessions need `/cloud/...` for the Laravel dispatcher.
     */
    function legacy_admin_menu_href(?string $moduleUrl): string
    {
        if ($moduleUrl === null || $moduleUrl === '' || $moduleUrl === '#') {
            return 'javascript:void(0)';
        }
        $slug = session('SESSION_ADMIN.slug', 'admin');
        if ($slug === 'cloud' && Str::startsWith($moduleUrl, '/admin/')) {
            return '/cloud/' . ltrim(substr($moduleUrl, 7), '/');
        }

        return $moduleUrl;
    }
}
