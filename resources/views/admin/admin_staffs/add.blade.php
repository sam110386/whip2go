@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Staff User')

@section('content')
    @php
        $u = $user ?? null;
        $isEdit = $u && !empty($u->id);
        $base = $basePath ?? '/admin/admin_staffs';
        $returnUrl = $base . '/index';
        $action = $formAction ?? ($base . '/add');
    @endphp

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage {{ $listTitle ?? 'Staff User' }}</span>
                </h4>
            </div>
            <div class="heading-elements">
                <div class="heading-btn-group">
                    <button type="submit" form="frmadmin" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Save' }}</button>
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

        @if($isEdit)
            <input type="hidden" name="User[id]" value="{{ $u->id }}">
        @endif

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
                            @foreach(($roles ?? []) as $rid => $rname)
                                <option value="{{ $rid }}" @selected((string)($u->role_id ?? '') === (string)$rid)>{{ $rname }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Username :@if(!$isEdit)<span class="text-danger">*</span>@endif</label>
                    <div class="col-lg-9">
                        @if($isEdit)
                            <p class="form-control-static"><strong>{{ $u->username ?? '' }}</strong></p>
                        @else
                            <input type="text" name="User[username]" class="form-control" value="{{ old('User.username', $u->username ?? '') }}" required>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">First Name :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="text" name="User[first_name]" class="form-control" value="{{ old('User.first_name', $u->first_name ?? '') }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Last Name :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="text" name="User[last_name]" class="form-control" value="{{ old('User.last_name', $u->last_name ?? '') }}" required>
                    </div>
                </div>

                @if($isEdit)
                    <div class="form-group">
                        <label class="col-lg-3 control-label">New Password :</label>
                        <div class="col-lg-9">
                            <input type="password" name="User[newpassword]" class="form-control" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label">Confirm Password :</label>
                        <div class="col-lg-9">
                            <input type="password" name="User[cnfpassword]" class="form-control" autocomplete="new-password">
                        </div>
                    </div>
                @endif

                <div class="form-group">
                    <label class="col-lg-3 control-label">Email :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="email" name="User[email]" class="form-control" value="{{ old('User.email', $u->email ?? '') }}" required>
                    </div>
                </div>

                @if(!$isEdit)
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
                @endif
            </div>
        </div>

        <div class="panel panel-flat">
            <div class="panel-heading">
                <h5 class="panel-title">Contact</h5>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">Address :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[address1]" class="form-control" value="{{ old('User.address1', $u->address1 ?? '') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">City :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[city]" class="form-control" value="{{ old('User.city', $u->city ?? '') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">State :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[other_state]" class="form-control" value="{{ old('User.other_state', $u->other_state ?? '') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Phone :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[contact_number]" class="form-control" value="{{ old('User.contact_number', $u->contact_number ?? '') }}" maxlength="14">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Status :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <select name="User[status]" class="form-control">
                            <option value="1" @selected((string)($u->status ?? '1') === '1')>Active</option>
                            <option value="0" @selected((string)($u->status ?? '') === '0')>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-12">
                <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Save' }}</button>
                <a href="{{ $returnUrl }}" class="btn btn-default">Return</a>
            </div>
        </div>
    </form>
@endsection
