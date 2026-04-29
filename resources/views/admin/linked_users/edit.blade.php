@extends('admin.layouts.app')

@section('title', !empty($user) ? 'Update User' : 'Add User')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">User</span> - {{ !empty($user) ? 'Update' : 'Add' }}
            </h4>
        </div>
        <div class="heading-elements">
            <button type="submit" form="frmadmin" class="btn btn-primary">Save</button>
            <a href="/cloud/linked_users/index" class="btn btn-default">Return</a>
        </div>
    </div>
</div>

<div class="content">
    @includeif('partials.flash')

    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-flat">
                <div class="panel-heading">
                    <h5 class="panel-title">{{ !empty($user) ? 'Update User' : 'Add User' }}</h5>
                </div>

                <div class="panel-body">
                    <form method="POST" action="/cloud/linked_users/edit{{ !empty($user->id) ? '/' . base64_encode((string)$user->id) : '' }}" id="frmadmin" name="frmadmin" class="form-horizontal">
                        @csrf

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">First name:</label>
                            <div class="col-lg-9">
                                <input type="text" name="User[first_name]" value="{{ $user->first_name ?? '' }}" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Last name:</label>
                            <div class="col-lg-9">
                                <input type="text" name="User[last_name]" value="{{ $user->last_name ?? '' }}" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Email:</label>
                            <div class="col-lg-9">
                                <input type="email" name="User[email]" value="{{ $user->email ?? '' }}" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Username:</label>
                            <div class="col-lg-9">
                                <input type="text" name="User[username]" value="{{ $user->username ?? '' }}" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Phone:</label>
                            <div class="col-lg-9">
                                <input type="text" name="User[contact_number]" value="{{ $user->contact_number ?? '' }}" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Password (leave blank to keep):</label>
                            <div class="col-lg-9">
                                <input type="password" name="User[password]" value="" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Status:</label>
                            <div class="col-lg-9">
                                <input type="number" name="User[status]" value="{{ $user->status ?? 1 }}" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Roles:</label>
                            <div class="col-lg-9">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="User[is_driver]" value="1" @checked(!empty($user->is_driver))> Is driver
                                </label>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="User[is_dealer]" value="1" @checked(!empty($user->is_dealer))> Is dealer
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-lg-9 col-lg-offset-3">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <a href="/cloud/linked_users/index" class="btn btn-default">Return</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
