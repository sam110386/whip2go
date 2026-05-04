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
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <form method="POST" action="{{ url('/admin/admins/profile') }}" id="frmadmin" name="frmadmin" class="form-horizontal">
            @csrf
            <input type="hidden" name="User[id]" value="{{ $user->id ?? '' }}">

            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label"> Username : </label>
                    <div class="col-lg-9">
                        <p class="form-control-static">{{ $user->username ?? '' }}</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label"><span class="text-danger">*</span> First Name :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[first_name]" class="form-control required" value="{{ $user->first_name ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label"><span class="text-danger">*</span> Last Name :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[last_name]" class="form-control required" value="{{ $user->last_name ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label"><span class="text-danger">*</span> Email Address :</label>
                    <div class="col-lg-9">
                        <input type="email" name="User[email]" class="form-control required" value="{{ $user->email ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label"><span class="text-danger">*</span> Address :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[address]" class="form-control required" value="{{ $user->address ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label"><span class="text-danger">*</span> City :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[city]" class="form-control required" value="{{ $user->city ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label"><span class="text-danger">*</span> State :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[state]" class="form-control required" value="{{ $user->state ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label"><span class="text-danger">*</span> Phone :</label>
                    <div class="col-lg-9">
                        <input type="text" name="User[contact_number]" class="form-control required" value="{{ $user->contact_number ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label"> Status :</label>
                    <div class="col-lg-9">
                        <select name="User[status]" class="form-control">
                            <option value="">Select..</option>
                            <option value="1" @selected(!empty($user) && (int) ($user->status ?? 0) === 1)>Active</option>
                            <option value="0" @selected(!empty($user) && (int) ($user->status ?? 0) === 0)>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-lg-offset-3 col-lg-9">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="button" class="btn btn-default" onclick="window.location.href='{{ $returnUrl }}'">Cancel</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
