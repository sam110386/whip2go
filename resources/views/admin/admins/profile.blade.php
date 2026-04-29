@extends('admin.layouts.app')

@section('title', 'Update Profile')

@section('content')
    @php
        $returnUrl = '/admin/admins/index';
    @endphp

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage Profile</span>
                </h4>
            </div>
            <div class="heading-elements">
                <div class="heading-btn-group">
                    <button type="submit" form="frmadmin" class="btn btn-primary">Update</button>
                    <a href="{{ $returnUrl }}" class="btn btn-default">Return</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <form method="POST" action="/admin/admins/profile" id="frmadmin" name="frmadmin" class="form-horizontal">
        @csrf
        <input type="hidden" name="User[id]" value="{{ $user->id ?? '' }}">

        <div class="panel panel-flat">
            <div class="panel-heading">
                <h5 class="panel-title">Account</h5>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">Username :</label>
                    <div class="col-lg-9">
                        <input type="text" class="form-control" value="{{ $user->username ?? '' }}" disabled>
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
                    <label class="col-lg-3 control-label">Status :</label>
                    <div class="col-lg-9">
                        <select name="User[status]" class="form-control">
                            <option value="1" @if(!empty($user) && (int)($user->status ?? 0) === 1) selected @endif>Active</option>
                            <option value="0" @if(!empty($user) && (int)($user->status ?? 0) === 0) selected @endif>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-flat">
            <div class="panel-heading">
                <h5 class="panel-title">Address</h5>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">Address 1 :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[address1]" class="form-control" value="{{ $user->address1 ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Address 2 :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[address2]" class="form-control" value="{{ $user->address2 ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">City :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[city]" class="form-control" value="{{ $user->city ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">State ID :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[state_id]" class="form-control" value="{{ $user->state_id ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Timezone :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[timezone]" class="form-control" value="{{ $user->timezone ?? '' }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-12">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ $returnUrl }}" class="btn btn-default">Return</a>
            </div>
        </div>
    </form>
@endsection
