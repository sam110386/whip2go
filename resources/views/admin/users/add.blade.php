@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Add User')

@section('content')
    <h1>{{ $listTitle ?? 'Add User' }}</h1>

    @if(!empty($error))
        <div style="padding:8px; background:#fee; color:#900; margin-bottom:10px;">{{ $error }}</div>
    @endif

    <form method="POST" action="{{ $formAction ?? '/admin/users/add' }}">
        @csrf
        <div style="display:grid; grid-template-columns: repeat(2,minmax(240px,1fr)); gap:10px;">
            <label>First Name
                <input type="text" name="User[first_name]" value="{{ old('User.first_name', $user->first_name ?? '') }}">
            </label>
            <label>Last Name
                <input type="text" name="User[last_name]" value="{{ old('User.last_name', $user->last_name ?? '') }}">
            </label>
            <label>Email
                <input type="email" name="User[email]" value="{{ old('User.email', $user->email ?? '') }}">
            </label>
            <label>Contact Number
                <input type="text" name="User[contact_number]" value="{{ old('User.contact_number', $user->contact_number ?? '') }}">
            </label>
            <label>Password
                <input type="password" name="User[pwd]" value="">
            </label>
        </div>
        <div style="margin-top:12px;">
            <button type="submit">Save</button>
            <a href="/admin/users/index" style="margin-left:10px;">Cancel</a>
        </div>
    </form>
@endsection

