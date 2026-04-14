@extends('admin.layouts.app')

@section('title', !empty($user) ? 'Update User' : 'Add User')

@section('content')
    <h1>{{ !empty($user) ? 'Update User' : 'Add User' }}</h1>
    <form method="POST" action="/cloud/linked_users/edit{{ !empty($user->id) ? '/' . base64_encode((string)$user->id) : '' }}">
        @csrf
        <label>First name<br><input type="text" name="User[first_name]" value="{{ $user->first_name ?? '' }}"></label><br><br>
        <label>Last name<br><input type="text" name="User[last_name]" value="{{ $user->last_name ?? '' }}"></label><br><br>
        <label>Email<br><input type="email" name="User[email]" value="{{ $user->email ?? '' }}"></label><br><br>
        <label>Username<br><input type="text" name="User[username]" value="{{ $user->username ?? '' }}"></label><br><br>
        <label>Phone<br><input type="text" name="User[contact_number]" value="{{ $user->contact_number ?? '' }}"></label><br><br>
        <label>Password (leave blank to keep)<br><input type="password" name="User[password]" value=""></label><br><br>
        <label>Status<br><input type="number" name="User[status]" value="{{ $user->status ?? 1 }}"></label><br><br>
        <label><input type="checkbox" name="User[is_driver]" value="1" @checked(!empty($user->is_driver))> Is driver</label><br>
        <label><input type="checkbox" name="User[is_dealer]" value="1" @checked(!empty($user->is_dealer))> Is dealer</label><br><br>
        <button type="submit">Save</button>
        <a href="/cloud/linked_users/index" style="margin-left:10px;">Cancel</a>
    </form>
@endsection
