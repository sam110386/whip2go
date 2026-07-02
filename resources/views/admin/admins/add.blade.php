@extends('admin.layouts.app')

@php
    $title ??= 'Add Admin User';
    $roles ??= [];
    $user ??= collect();
@endphp

@section('title', $title)

@section('content')
    <div class="panel">
        <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;padding: 13px 23px 0;">
            <h3 style="width: 80%; float: left;">
                {{$title}}
            </h3>
        </section>

        <div class="row">
            @includeif('partials.flash')
        </div>

        <div class="row">
            <form method="POST" action="{{ url('admin/admins/add/' . base64_encode(data_get($user, 'id'))) }}" id="frmadmin"
                name="frmadmin" class="form-horizontal">
                @csrf

                <fieldset class="col-lg-12">
                    <div class="panel-body">

                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="col-lg-3 control-label">
                                    Role <span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-9">
                                    <select name="User[role_id]" id="UserRoleId" class="form-control required">
                                        <option value="">Select Role</option>
                                        @foreach ($roles as $id => $name)
                                            <option value="{{ $id }}" @selected(data_get($user, 'role_id', '') == $id)>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                @if (empty(data_get($user, 'id', '')))
                                    <label class="col-lg-3 control-label">
                                        Username :<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-lg-9">
                                        <input type="text" name="User[username]" class="form-control required"
                                            value="{{ data_get($user, 'username', '') }}">
                                    </div>
                                @else
                                    <label class="col-lg-3 control-label">
                                        Username :
                                    </label>
                                    <div class="col-lg-9">
                                        <p class="form-control-static">{{ data_get($user, 'username', '') }}</p>
                                        <input type="hidden" name="User[username]"
                                            value="{{ data_get($user, 'username', '') }}">
                                    </div>
                                @endif
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">
                                    First Name :<span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="User[first_name]" class="form-control required"
                                        value="{{ data_get($user, 'first_name', '') }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">
                                    Last Name:<span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="User[last_name]" class="form-control required"
                                        value="{{ data_get($user, 'last_name', '')}}">
                                </div>
                            </div>

                            @if (!empty(data_get($user, 'id', '')))
                                <div class="form-group">
                                    <label class="col-lg-3 control-label">
                                        New Password :<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-lg-9">
                                        <input type="password" name="User[newpassword]" id="password1" class="form-control"
                                            autocomplete="new-password">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-3 control-label">
                                        Confirm Password:<span class="text-danger">*</span>
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
                                    <input type="email" name="User[email]" class="form-control required"
                                        value="{{ data_get($user, 'email') }}">
                                </div>
                            </div>

                            @if (empty(data_get($user, 'id', '')))
                                <div class="form-group">
                                    <label class="col-lg-3 control-label">
                                        Password :<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-lg-9">
                                        <input type="password" name="User[npwd]" id="password" class="form-control required"
                                            maxlength="20" minlength="6" autocomplete="new-password">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-lg-3 control-label">
                                        Confirm Password:<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-lg-9">
                                        <input type="password" name="User[conpwd]" class="form-control" maxlength="20"
                                            minlength="6" autocomplete="new-password">
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-lg-3 control-label">
                                    Address :
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="User[address]" class="form-control"
                                        value="{{ data_get($user, 'address') }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-3 control-label">
                                    City :
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="User[city]" class="form-control"
                                        value="{{ data_get($user, 'city') }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-3 control-label">
                                    State :
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="User[state]" class="form-control"
                                        value="{{ data_get($user, 'state') }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-3 control-label">
                                    Phone :
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="User[contact_number]" id="number4" class="form-control phone"
                                        maxlength="14" value="{{ data_get($user, 'contact_number')  }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-3 control-label">
                                    Status :<span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-9">
                                    <select name="User[status]" class="form-control">
                                        <option value="1" @selected(data_get($user, 'status', 0) == 1)>
                                            Active
                                        </option>
                                        <option value="0" @selected(data_get($user, 'status', 0) == 0)>
                                            Inactive
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-3 control-label">
                                    Staff Role <span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-9 multi-select-full">
                                    <select name="User[staff_role_id][]" id="UserStaffRoleId" class="form-control"
                                        multiple="multiple">
                                    </select>
                                    <span class="help-block">
                                        <em>
                                            These will be listed when this user will create his staff member
                                        </em>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="col-lg-8">
                                    <button type="submit" class="btn btn-primary">
                                        {{ !empty(data_get($user, 'id', '')) ? 'Update' : 'Save' }}
                                    </button>
                                    <button type="button" class="btn btn-default" onclick="goBack('/admin/admins/index')">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <input type="hidden" name="User[id]" id="UserId" value="{{ data_get($user, 'id', '') }}">
            </form>
        </div>
    </div>

@endsection

@push('scripts')

    <script type="text/javascript">

        $(document).ready(function () {
            jQuery("#password").bind("keypress", function (e) {
                if (e.which == 32) {
                    return false;
                }
            });

            jQuery("#UserConpwd").bind("keypress", function (e) {
                if (e.which == 32) {
                    return false;
                }
            });

            jQuery('#UserStaffRoleId').selectpicker({
                styleBase: 'form-control'
            });

            jQuery("#UserRoleId").change(function () {
                jQuery.post('{{ url('admin/roles/getsubrole') }}', {
                    roleid: jQuery(this).val(),
                    userid: jQuery("#UserId").val()
                }, function (html) {
                    jQuery("#UserStaffRoleId").html(html);
                    jQuery('#UserStaffRoleId').selectpicker('refresh');;
                });
            });
        });

        @if (empty(data_get($user, 'id', '')))
            jQuery(document).ready(function () {
                jQuery("#frmadmin").validate({
                    rules: {
                        "User[npwd]": {
                            required: true
                        },
                        "User[conpwd]": {
                            required: true,
                            equalTo: "#password"
                        },
                        "User[city]": {
                            strings: true,
                        },
                        "User[state]": {
                            strings: true,
                        }
                    },
                    messages: {
                        "User[conpwd]": {
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
                        "User[newpassword]": {
                            required: false
                        },
                        "User[cnfpassword]": {
                            required: false,
                            equalTo: "#password1"
                        },
                    },
                    messages: {
                        "User[cnfpassword]": {
                            equalTo: "Passwords do not match. Please re-enter both passwords."
                        }
                    }
                });

                jQuery.post('{{ url('admin/roles/getsubrole') }}', {
                    roleid: '{{ data_get($user, 'role_id', '') }}',
                    userid: '{{ data_get($user, 'id', '') }}',
                }, function (html) {
                    jQuery("#UserStaffRoleId").html(html);
                    jQuery('#UserStaffRoleId').selectpicker('refresh');;
                });
            });
        @endif

    </script>
@endpush