@extends('admin.layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <h1>Admin Dashboard</h1>

    @php
        $admin = session('SESSION_ADMIN');
        $name = is_array($admin) ? ($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '') : '';
        $adminModules = $adminModules ?? [];
    @endphp

    <div style="margin: 10px 0;">
        <strong>Signed in:</strong> {{ trim($name) ?: 'unknown' }}
    </div>

    <h2>Menu Modules (debug)</h2>
    @if(is_array($adminModules) && count($adminModules))
        <ul>
            @foreach($adminModules as $m)
                <li>
                    {{ $m['module'] ?? '' }}
                    @if(!empty($m['module_url']))
                        - <code>{{ $m['module_url'] }}</code>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <div>No admin modules loaded (yet).</div>
    @endif
@endsection

