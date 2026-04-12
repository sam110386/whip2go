@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Staff User')

@section('content')
    <h1>
        <a href="{{ $basePath }}/index">←</a>
        {{ $listTitle ?? 'Staff User' }}
    </h1>

    @if(session('success'))
        <p style="color:green;">{{ session('success') }}</p>
    @endif
    @if(session('error'))
        <p style="color:red;">{{ session('error') }}</p>
    @endif

    @php
        $u = $user ?? null;
        $isEdit = $u && !empty($u->id);
    @endphp

    <form method="post" action="{{ $formAction ?? ($basePath . '/admin_add') }}" class="form-horizontal">
        <div style="display:flex; flex-wrap:wrap; gap:24px;">
            <div style="flex:1; min-width:280px;">
                <p>
                    <label>Role <span style="color:red">*</span><br>
                        <select name="User[role_id]" class="form-control" required>
                            <option value="">Select Role</option>
                            @foreach(($roles ?? []) as $rid => $rname)
                                <option value="{{ $rid }}" @selected((string)($u->role_id ?? '') === (string)$rid)>{{ $rname }}</option>
                            @endforeach
                        </select>
                    </label>
                </p>
                <p>
                    <label>Username @if(!$isEdit)<span style="color:red">*</span>@endif<br>
                        @if($isEdit)
                            <strong>{{ $u->username ?? '' }}</strong>
                        @else
                            <input type="text" name="User[username]" class="form-control" value="{{ old('User.username', $u->username ?? '') }}" required>
                        @endif
                    </label>
                </p>
                <p>
                    <label>First Name <span style="color:red">*</span><br>
                        <input type="text" name="User[first_name]" class="form-control" value="{{ old('User.first_name', $u->first_name ?? '') }}" required>
                    </label>
                </p>
                <p>
                    <label>Last Name <span style="color:red">*</span><br>
                        <input type="text" name="User[last_name]" class="form-control" value="{{ old('User.last_name', $u->last_name ?? '') }}" required>
                    </label>
                </p>
                @if($isEdit)
                    <p>
                        <label>New Password<br>
                            <input type="password" name="User[newpassword]" class="form-control" autocomplete="new-password">
                        </label>
                    </p>
                    <p>
                        <label>Confirm Password<br>
                            <input type="password" name="User[cnfpassword]" class="form-control" autocomplete="new-password">
                        </label>
                    </p>
                @endif
                <p>
                    <label>Email <span style="color:red">*</span><br>
                        <input type="email" name="User[email]" class="form-control" value="{{ old('User.email', $u->email ?? '') }}" required>
                    </label>
                </p>
                @if(!$isEdit)
                    <p>
                        <label>Password <span style="color:red">*</span><br>
                            <input type="password" name="User[npwd]" class="form-control" required autocomplete="new-password">
                        </label>
                    </p>
                    <p>
                        <label>Confirm Password <span style="color:red">*</span><br>
                            <input type="password" name="User[conpwd]" class="form-control" required autocomplete="new-password">
                        </label>
                    </p>
                @endif
            </div>
            <div style="flex:1; min-width:280px;">
                <p>
                    <label>Address<br>
                        <input type="text" name="User[address1]" class="form-control" value="{{ old('User.address1', $u->address1 ?? '') }}">
                    </label>
                </p>
                <p>
                    <label>City<br>
                        <input type="text" name="User[city]" class="form-control" value="{{ old('User.city', $u->city ?? '') }}">
                    </label>
                </p>
                <p>
                    <label>State<br>
                        <input type="text" name="User[other_state]" class="form-control" value="{{ old('User.other_state', $u->other_state ?? '') }}">
                    </label>
                </p>
                <p>
                    <label>Phone<br>
                        <input type="text" name="User[contact_number]" class="form-control" value="{{ old('User.contact_number', $u->contact_number ?? '') }}" maxlength="14">
                    </label>
                </p>
                <p>
                    <label>Status <span style="color:red">*</span><br>
                        <select name="User[status]" class="form-control">
                            <option value="1" @selected((string)($u->status ?? '1') === '1')>Active</option>
                            <option value="0" @selected((string)($u->status ?? '') === '0')>Inactive</option>
                        </select>
                    </label>
                </p>
            </div>
        </div>
        <p>
            @if($isEdit)
                <button type="submit" class="btn">Update</button>
            @else
                <button type="submit" class="btn">Save</button>
            @endif
            <button type="button" class="btn" onclick="window.location='{{ $basePath }}/index'">Cancel</button>
        </p>
        @if($isEdit)
            <input type="hidden" name="User[id]" value="{{ $u->id }}">
        @endif
    </form>
@endsection
