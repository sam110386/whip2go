@extends('admin.layouts.app')

@section('title', 'Change Password')

@section('content')
    <h1>Change Password</h1>

    @if(!empty($error))
        <div style="color:#b00020; margin: 8px 0;">
            {{ $error }}
        </div>
    @endif

    <form method="POST" action="/admin/admins/admin_change_password" style="display:flex; flex-direction:column; gap:12px; max-width: 420px;">
        @csrf
        <label>
            Old Password
            <input type="password" name="User[oldPassword]" required>
        </label>
        <label>
            New Password
            <input type="password" name="User[newpassword]" required>
        </label>
        <label>
            Confirm Password
            <input type="password" name="User[confirmpassword]" required>
        </label>

        <div style="display:flex; gap:10px;">
            <button type="submit">Submit</button>
            <a href="/admin/homes/dashboard" style="align-self:center;">Cancel</a>
        </div>
    </form>
@endsection

