@extends('admin.layouts.app')

@section('title', 'Admin Login')

@section('content')
    <h1>Admin Login</h1>

    @if(!empty($error))
        <div style="color: #b00020; margin: 8px 0;">
            {{ $error }}
        </div>
    @endif

    <form method="POST" action="/admin/admins/login" style="display:flex; flex-direction:column; gap:10px; max-width: 320px;">
        @csrf
        <input type="hidden" name="referred_url" value="{{ $referred_url ?? '' }}">

        <label>
            Username
            <input type="text" name="username" value="{{ $username ?? '' }}" required>
        </label>

        <label>
            Password
            <input type="password" name="password" required>
        </label>

        <button type="submit">Login</button>
    </form>
@endsection

