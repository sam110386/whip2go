@extends('admin.layouts.app')

@php
    $title ??= 'Add Staff User';
    $roles ??= [];
    $user ??= collect();
@endphp

@section('title', $title)

@section('content')

    <div class="panel">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <a href="{{ url('admin/admin_staffs/index') }}">
                        <i class="icon-arrow-left52 position-left"></i>
                        <span class="text-semibold">Manage {{ $title }}</span>
                    </a>
                </h4>
            </div>
        </div>

        <div class="row">
            @includeif('partials.flash')
        </div>

        <div class="row">
            <fieldset class="col-lg-12">
                <form method="POST" action="{{ url('admin/admin_staffs/add')}}" id="frmadmin" name="frmadmin"
                    class="form-horizontal">
                    @csrf

                    <div class="panel-body">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="col-lg-3 control-label">
                                    Role :<span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-9">
                                    <select name="User[role_id]" class="form-control" required>
                                        <option value="">Select Role</option>
                                        @foreach(($roles) as $rid => $rname)
                                            <option value="{{ $rid }}" @selected(data_get($user, 'role_id', '') == $rid)>
                                                {{ $rname }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">
                                    Username :@if(empty(data_get($user, 'id', '')))<span class="text-danger">*</span>@endif
                                </label>
                                <div class="col-lg-9">
                                    @if(!empty(data_get($user, 'id', '')))
                                        <p class="form-control-static"><strong>{{ data_get($user, 'username', '') }}</strong>
                                        </p>
                                    @else
                                        <input type="text" name="User[username]" class="form-control"
                                            value="{{ old('User.username', data_get($user, 'username', '')) }}" required>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">
                                    First Name :<span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="User[first_name]" class="form-control"
                                        value="{{ old('User.first_name', data_get($user, 'first_name', '')) }}" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">
                                    Last Name :<span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="User[last_name]" class="form-control"
                                        value="{{ old('User.last_name', data_get($user, 'last_name', '')) }}" required>
                                </div>
                            </div>

                            @if(!empty(data_get($user, 'id', '')))
                                <div class="form-group">
                                    <label class="col-lg-3 control-label">
                                        New Password :<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-lg-9">
                                        <input type="password" name="User[newpassword]" class="form-control"
                                            autocomplete="new-password">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-3 control-label">
                                        Confirm Password :<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-lg-9">
                                        <input type="password" name="User[cnfpassword]" class="form-control"
                                            autocomplete="new-password">
                                    </div>
                                </div>
                            @endif

                            <div class="form-group">
                                <label class="col-lg-3 control-label">
                                    Email :<span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-9">
                                    <input type="email" name="User[email]" class="form-control"
                                        value="{{ old('User.email', data_get($user, 'email', '')) }}" required>
                                </div>
                            </div>

                            @if(empty(data_get($user, 'id', '')))
                                <div class="form-group">
                                    <label class="col-lg-3 control-label">
                                        Password :<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-lg-9">
                                        <input type="password" name="User[npwd]" class="form-control" required
                                            autocomplete="new-password">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-3 control-label">
                                        Confirm Password :<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-lg-9">
                                        <input type="password" name="User[conpwd]" class="form-control" required
                                            autocomplete="new-password">
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="col-lg-3 control-label">Address :</label>
                                <div class="col-lg-9">
                                    <input type="text" name="User[address1]" class="form-control"
                                        value="{{ old('User.address1', data_get($user, 'address1', '')) }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">City :</label>
                                <div class="col-lg-9">
                                    <input type="text" name="User[city]" class="form-control"
                                        value="{{ old('User.city', data_get($user, 'city', '')) }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">State :</label>
                                <div class="col-lg-9">
                                    <input type="text" name="User[other_state]" class="form-control"
                                        value="{{ old('User.other_state', data_get($user, 'other_state', '')) }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Phone :</label>
                                <div class="col-lg-9">
                                    <input type="text" name="User[contact_number]" class="form-control"
                                        value="{{ old('User.contact_number', data_get($user, 'contact_number', '')) }}"
                                        maxlength="14">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Status :<span class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <select name="User[status]" class="form-control">
                                        <option value="1" @selected(data_get($user, 'status', 1) == '1')>Active</option>
                                        <option value="0" @selected(data_get($user, 'status', 1) == '0')>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="col-lg-8">
                                    <button type="submit" class="btn btn-primary">
                                        {{ !empty(data_get($user, 'id', '')) ? 'Update' : 'Save' }}
                                    </button>
                                    <a href="{{ url('admin/admin_staffs/index')}}" class="btn btn-default">Return</a>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="User[id]" value="{{ data_get($user, 'id', '') }}">

                </form>
            </fieldset>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        jQuery(document).ready(function () {
            jQuery("#password").bind("keypress", function (e) {
                if (e.which == 32) {
                    return false;
                }
            });
            jQuery("#AdminConpwd").bind("keypress", function (e) {
                if (e.which == 32) {
                    return false;
                }
            });
        });

        @if (empty(data_get($user, 'id', '')))
            jQuery(document).ready(function () {
                jQuery("#frmadmin").validate({
                    rules: {
                        "data[User][npwd]": {
                            required: true
                        },
                        "data[User][conpwd]": {
                            required: true,
                            equalTo: "#password"
                        },
                        "data[User][city]": {
                            strings: true,
                        },
                        "data[User][other_state]": {
                            strings: true,
                        }
                    },
                    messages: {
                        "data[User][conpwd]": {
                            equalTo: "Passwords do not match. Please re-enter both passwords."
                        }
                    }
                });
            });
        @endif

        @if (!empty(data_get($user, 'id', '')))
            jQuery(document).ready(function () {
                jQuery("#frmadmin").validate({
                    rules: {
                        "data[User][newpassword]": {
                            required: false
                        },
                        "data[User][cnfpassword]": {
                            required: false,
                            equalTo: "#password1"
                        },
                    },
                    messages: {
                        "data[User][cnfpassword]": {
                            equalTo: "Passwords do not match. Please re-enter both passwords."
                        }
                    }
                });
            });
        @endif

    </script>
@endpush