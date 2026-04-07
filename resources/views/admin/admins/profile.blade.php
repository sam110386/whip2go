@extends('admin.layouts.app')

@section('title', 'Update Profile')

@section('content')
    <h1>Update Profile</h1>

    @if(!empty($error))
        <div style="color:#b00020; margin: 8px 0;">
            {{ $error }}
        </div>
    @endif

    <form method="POST" action="/admin/admins/admin_profile" style="display:flex; flex-direction:column; gap:12px; max-width: 520px;">
        @csrf

        <input type="hidden" name="User[id]" value="{{ $user->id ?? '' }}">

        <label>
            Username
            <input type="text" value="{{ $user->username ?? '' }}" disabled>
        </label>

        <label>
            First Name
            <input type="text" name="User[first_name]" value="{{ $user->first_name ?? '' }}" required>
        </label>

        <label>
            Last Name
            <input type="text" name="User[last_name]" value="{{ $user->last_name ?? '' }}" required>
        </label>

        <label>
            Email
            <input type="email" name="User[email]" value="{{ $user->email ?? '' }}" required>
        </label>

        <label>
            Contact #
            <input type="text" name="User[contact_number]" value="{{ $user->contact_number ?? '' }}">
        </label>

        <label>
            Status
            <select name="User[status]">
                <option value="1" @if(!empty($user) && (int)($user->status ?? 0) === 1) selected @endif>Active</option>
                <option value="0" @if(!empty($user) && (int)($user->status ?? 0) === 0) selected @endif>Inactive</option>
            </select>
        </label>

        <label>
            Address 1
            <input type="text" name="User[address1]" value="{{ $user->address1 ?? '' }}">
        </label>

        <label>
            Address 2
            <input type="text" name="User[address2]" value="{{ $user->address2 ?? '' }}">
        </label>

        <label>
            City
            <input type="text" name="User[city]" value="{{ $user->city ?? '' }}">
        </label>

        <label>
            State ID
            <input type="text" name="User[state_id]" value="{{ $user->state_id ?? '' }}">
        </label>

        <label>
            Timezone
            <input type="text" name="User[timezone]" value="{{ $user->timezone ?? '' }}">
        </label>

        <div style="display:flex; gap:10px;">
            <button type="submit">Update</button>
            <a href="/admin/admins/index" style="align-self:center;">Cancel</a>
        </div>
    </form>
@endsection

