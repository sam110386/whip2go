@extends('admin.layouts.app')

@section('title', !empty($listTitle) ? $listTitle : 'Admin Add')

@section('content')
    <h1>{{ !empty($listTitle) ? $listTitle : 'Admin User' }}</h1>

    @if(!empty($error))
        <div style="color:#b00020; margin: 8px 0;">
            {{ $error }}
        </div>
    @endif

    <form method="POST" action="{{ $formAction ?? '/admin/admins/add' }}" style="display:flex; flex-direction:column; gap:12px; max-width: 520px;">
        @csrf
        <input type="hidden" name="User[id]" value="{{ $user->id ?? '' }}">

        <label>
            Role
            <select name="User[role_id]" required>
                <option value="">Select Role</option>
                @foreach(($roles ?? []) as $id => $name)
                    <option value="{{ $id }}" @if(!empty($user) && (string)($user->role_id ?? '') === (string)$id) selected @endif>{{ $name }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Username
            <input type="text" name="User[username]" value="{{ $user->username ?? '' }}" required @if(!empty($user) && !empty($user->id)) disabled @endif>
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
            <select name="User[status]" required>
                <option value="1" @if(empty($user) || (int)($user->status ?? 0) === 1) selected @endif>Active</option>
                <option value="0" @if(!empty($user) && (int)($user->status ?? 0) === 0) selected @endif>Inactive</option>
            </select>
        </label>

        <label>
            Staff Roles (multiple)
            <select name="User[staff_role_id][]" multiple size="6">
                @foreach(($roles ?? []) as $rid => $rname)
                    <option value="{{ $rid }}"
                        @if(!empty($userStaffRoleIds) && is_array($userStaffRoleIds) && in_array((string)$rid, array_map('strval', $userStaffRoleIds), true)) selected @endif>
                        {{ $rname }}
                    </option>
                @endforeach
            </select>
        </label>

        @php $isEditing = !empty($user) && !empty($user->id); @endphp

        @if(!$isEditing)
            <label>
                Password
                <input type="password" name="User[npwd]" required>
            </label>
            <label>
                Confirm Password
                <input type="password" name="User[conpwd]" required>
            </label>
        @else
            <label>
                New Password (optional)
                <input type="password" name="User[newpassword]">
            </label>
            <label>
                Confirm New Password (optional)
                <input type="password" name="User[cnfpassword]">
            </label>
        @endif

        <div style="display:flex; gap:10px;">
            <button type="submit">{{ $isEditing ? 'Update' : 'Save' }}</button>
            <a href="/admin/admins/index" style="align-self:center;">Cancel</a>
        </div>
    </form>
@endsection

