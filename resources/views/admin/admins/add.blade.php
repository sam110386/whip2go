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
                    <span class="text-semibold">{{ $listTitle ?? 'Add Admin User' }}</span>
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <form method="POST" action="{{ $action }}" id="frmadmin" name="frmadmin" class="form-horizontal">
            @csrf
            <input type="hidden" name="User[id]" id="UserId" value="{{ $user->id ?? '' }}">

            <div class="panel-body">
                <fieldset class="col-lg-12">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="col-lg-3 control-label"> Role <span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <select name="User[role_id]" id="UserRoleId" class="form-control required">
                                    <option value="">Select Role</option>
                                    @foreach (($roles ?? []) as $id => $name)
                                        <option value="{{ $id }}" @selected(!empty($user) && (string) ($user->role_id ?? '') === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            @if (!$isEditing)
                                <label class="col-lg-3 control-label">Username :<span class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <input type="text" name="User[username]" class="form-control required"
                                        value="{{ $user->username ?? '' }}">
                                </div>
                            @else
                                <label class="col-lg-3 control-label"> Username : </label>
                                <div class="col-lg-9">
                                    <p class="form-control-static">{{ $user->username ?? '' }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label"> First Name :<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="text" name="User[first_name]" class="form-control required"
                                    value="{{ $user->first_name ?? '' }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label"> Last Name:<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="text" name="User[last_name]" class="form-control required"
                                    value="{{ $user->last_name ?? '' }}">
                            </div>
                        </div>

                        @if ($isEditing)
                            <div class="form-group">
                                <label class="col-lg-3 control-label">New Password :<span class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <input type="password" name="User[newpassword]" id="password1" class="form-control"
                                        autocomplete="new-password">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-3 control-label">Confirm Password:<span
                                        class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <input type="password" name="User[cnfpassword]" class="form-control"
                                        autocomplete="new-password">
                                </div>
                            </div>
                        @endif

                        <div class="form-group">
                            <label class="col-lg-3 control-label">Email :<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="email" name="User[email]" class="form-control required"
                                    value="{{ $user->email ?? '' }}">
                            </div>
                        </div>

                        @if (!$isEditing)
                            <div class="form-group">
                                <label class="col-lg-3 control-label">Password :<span class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <input type="password" name="User[npwd]" id="password" class="form-control required"
                                        maxlength="20" minlength="6" autocomplete="new-password">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Confirm Password:<span
                                        class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <input type="password" name="User[conpwd]" class="form-control" maxlength="20" minlength="6"
                                        autocomplete="new-password">
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="col-lg-3 control-label"> Address :</label>
                            <div class="col-lg-9">
                                <input type="text" name="User[address]" class="form-control"
                                    value="{{ $user->address ?? '' }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label"> City :</label>
                            <div class="col-lg-9">
                                <input type="text" name="User[city]" class="form-control" value="{{ $user->city ?? '' }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">State :</label>
                            <div class="col-lg-9">
                                <input type="text" name="User[state]" class="form-control" value="{{ $user->state ?? '' }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Phone :</label>
                            <div class="col-lg-9">
                                <input type="text" name="User[contact_number]" id="number4" class="form-control phone"
                                    maxlength="14" value="{{ $user->contact_number ?? '' }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Status :<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <select name="User[status]" class="form-control">
                                    <option value="1" @selected(!empty($user) && (int) ($user->status ?? 0) === 1)>Active
                                    </option>
                                    <option value="0" @selected(!empty($user) && (int) ($user->status ?? 0) === 0)>Inactive
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label"> Staff Role <span class="text-danger">*</span></label>
                            <div class="col-lg-9 multi-select-full">
                                <select name="User[staff_role_id][]" id="UserStaffRoleId" class="form-control"
                                    multiple="multiple">
                                    {{-- Populated via AJAX --}}
                                </select>
                                <span class="help-block"><em>These will be listed when this user will create his staff
                                        member</em></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="col-lg-8">
                                <button type="submit" class="btn btn-primary">{{ $isEditing ? 'Update' : 'Save' }}</button>
                                <button type="button" class="btn btn-default"
                                    onclick="window.location.href='{{ $returnUrl }}'">Cancel</button>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            // Block spaces in password fields
            $('#password, #password1, input[name="User[conpwd]"], input[name="User[cnfpassword]"]').on('keypress', function (e) {
                if (e.which == 32) {
                    return false;
                }
            });

            function loadSubRoles(roleId, userId) {
                $.ajax({
                    url: "{{ url('/admin/roles/getsubrole') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        roleid: roleId,
                        userid: userId
                    },
                    success: function (html) {
                        $('#UserStaffRoleId').html(html);
                        if ($.fn.selectpicker) {
                            $('#UserStaffRoleId').selectpicker('refresh');
                        }
                    }
                });
            }

            var currentRoleId = $('#UserRoleId').val();
            var currentUserId = $('#UserId').val();
            if (currentRoleId) {
                loadSubRoles(currentRoleId, currentUserId);
            }

            $('#UserRoleId').change(function () {
                loadSubRoles($(this).val(), currentUserId);
            });

            if ($.fn.validate) {
                $("#frmadmin").validate({
                    rules: {
                        "User[npwd]": {
                            required: true,
                            minlength: 6
                        },
                        "User[conpwd]": {
                            required: true,
                            minlength: 6,
                            equalTo: "#password"
                        },
                        "User[newpassword]": {
                            required: false,
                            minlength: 6
                        },
                        "User[cnfpassword]": {
                            required: false,
                            minlength: 6,
                            equalTo: "#password1"
                        }
                    },
                    messages: {
                        "User[conpwd]": {
                            equalTo: "Passwords do not match. Please re-enter both passwords."
                        },
                        "User[cnfpassword]": {
                            equalTo: "Passwords do not match. Please re-enter both passwords."
                        }
                    },
                    errorElement: 'span',
                    errorClass: 'help-block text-danger',
                    highlight: function (element) {
                        $(element).closest('.form-group').addClass('has-error');
                    },
                    unhighlight: function (element) {
                        $(element).closest('.form-group').removeClass('has-error');
                    },
                    errorPlacement: function (error, element) {
                        if (element.parent('.input-group').length) {
                            error.insertAfter(element.parent());
                        } else {
                            error.insertAfter(element);
                        }
                    }
                });
            }

            if ($.fn.selectpicker) {
                $('#UserStaffRoleId').selectpicker({
                    styleBase: 'form-control'
                });
            }
        });
    </script>
@endpush