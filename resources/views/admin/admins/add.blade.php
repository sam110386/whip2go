@extends('admin.layouts.app')

@section('title', !empty($listTitle) ? $listTitle : 'Admin Add')

@section('content')
    @php
        $isEditing = !empty($user) && !empty($user->id);
        $returnUrl = '/admin/admins/index';
        $action = $formAction ?? '/admin/admins/add';
    @endphp

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage {{ !empty($listTitle) ? $listTitle : 'Admin User' }}</span>
                </h4>
            </div>
            <div class="heading-elements">
                <div class="heading-btn-group">
                    <button type="submit" form="frmadmin" class="btn btn-primary">{{ $isEditing ? 'Update' : 'Save' }}</button>
                    <a href="{{ $returnUrl }}" class="btn btn-default">Return</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <form method="POST" action="{{ $action }}" id="frmadmin" name="frmadmin" class="form-horizontal">
        @csrf
        <input type="hidden" name="User[id]" value="{{ $user->id ?? '' }}">

        <div class="panel panel-flat">
            <div class="panel-heading">
                <h5 class="panel-title">Account</h5>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">Role :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <select name="User[role_id]" class="form-control" required>
                            <option value="">Select Role</option>
                            @foreach(($roles ?? []) as $id => $name)
                                <option value="{{ $id }}" @if(!empty($user) && (string)($user->role_id ?? '') === (string)$id) selected @endif>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Username :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="text" name="User[username]" class="form-control" value="{{ $user->username ?? '' }}" required @if(!empty($user) && !empty($user->id)) disabled @endif>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">First Name :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="text" name="User[first_name]" class="form-control" value="{{ $user->first_name ?? '' }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Last Name :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="text" name="User[last_name]" class="form-control" value="{{ $user->last_name ?? '' }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Email :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="email" name="User[email]" class="form-control" value="{{ $user->email ?? '' }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Contact # :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[contact_number]" class="form-control" value="{{ $user->contact_number ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Status :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <select name="User[status]" class="form-control" required>
                            <option value="1" @if(empty($user) || (int)($user->status ?? 0) === 1) selected @endif>Active</option>
                            <option value="0" @if(!empty($user) && (int)($user->status ?? 0) === 0) selected @endif>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Staff Roles :</label>
                    <div class="col-lg-9">
                        <select name="User[staff_role_id][]" class="form-control" multiple size="6">
                            @foreach(($roles ?? []) as $rid => $rname)
                                <option value="{{ $rid }}"
                                    @if(!empty($userStaffRoleIds) && is_array($userStaffRoleIds) && in_array((string)$rid, array_map('strval', $userStaffRoleIds), true)) selected @endif>
                                    {{ $rname }}
                                </option>
                            @endforeach
                        </select>
                        <span class="help-block"><em>These will be listed when this user will create his staff member.</em></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-flat">
            <div class="panel-heading">
                <h5 class="panel-title">{{ $isEditing ? 'Change Password' : 'Set Password' }}</h5>
            </div>
            <div class="panel-body">
                @if(!$isEditing)
                    <div class="form-group">
                        <label class="col-lg-3 control-label">Password :<span class="text-danger">*</span></label>
                        <div class="col-lg-9">
                            <input type="password" name="User[npwd]" class="form-control" required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label">Confirm Password :<span class="text-danger">*</span></label>
                        <div class="col-lg-9">
                            <input type="password" name="User[conpwd]" class="form-control" required autocomplete="new-password">
                        </div>
                    </div>
                @else
                    <div class="form-group">
                        <label class="col-lg-3 control-label">New Password :</label>
                        <div class="col-lg-9">
                            <input type="password" name="User[newpassword]" class="form-control" autocomplete="new-password">
                            <span class="help-block">Leave blank to keep current password.</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label">Confirm New Password :</label>
                        <div class="col-lg-9">
                            <input type="password" name="User[cnfpassword]" class="form-control" autocomplete="new-password">
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-12">
                <button type="submit" class="btn btn-primary">{{ $isEditing ? 'Update' : 'Save' }}</button>
                <a href="{{ $returnUrl }}" class="btn btn-default">Return</a>
            </div>
        </div>
    </form>
@endsection
