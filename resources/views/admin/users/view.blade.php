@extends('admin.layouts.app')

@section('title', $listTitle)

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">
                        {{ $listTitle }}
                    </span>
                </h4>
            </div>
            <div class="heading-elements">
                <button type="button" class="btn left-margin btn-danger" onClick="goBack('/admin/users/index')">
                    {{ 'Return' }}
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div class="form-horizontal">
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        {{ 'First Name :' }}
                    </label>
                    <div class="col-lg-4">
                        {{ $user->first_name }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        {{ 'Last Name :' }}
                    </label>
                    <div class="col-lg-4">{{ $user->last_name }}</div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        {{ 'Email :' }}
                    </label>
                    <div class="col-lg-4">
                        {{ $user->email }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        {{ 'Notification Email :' }}
                    </label>
                    <div class="col-lg-4">
                        {{ $user->notify_email }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        {{ 'Phone # :' }}
                    </label>
                    <div class="col-lg-4">
                        {{ $user->contact_number }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        {{ 'DOB:' }}
                    </label>
                    <div class="col-lg-4">
                        {{ $user->dob }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        {{ 'Social S. # :' }}
                    </label>
                    <div class="col-lg-4">
                        {{ !empty($user->ss_no) ? \App\Helpers\Legacy\Security::decrypt($user->ss_no) : '' }}
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        {{ 'License # :' }}
                    </label>
                    <div class="col-lg-4">
                        {{ !empty($user->licence_number) ? \App\Helpers\Legacy\Security::decrypt($user->licence_number) : '' }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        {{'License Type :'}}
                    </label>
                    <div class="col-lg-4">
                        {{ $user->licence_type }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        {{'License Exp Date :'}}
                    </label>
                    <div class="col-lg-4">
                        {{ $user->licence_exp_date }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">&nbsp;</label>
                    <div class="inputs">
                        <div>
                            <div id="old_pic">
                                <img width='150' height='150'
                                    src="{{ asset('files/userdocs/' . ($user->license_doc_1 ?: 'no_image.gif')) }}">
                            </div>
                        </div>
                        <div style="clear:both;"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">&nbsp;</label>
                    <div class="inputs">
                        <div style="float:left;width:150px;height:150px;">
                            <div id="old_pic">
                                <img width='150' height='150'
                                    src="{{ asset('files/userdocs/' . ($user->license_doc_2 ?: 'no_image.gif')) }}">
                            </div>
                        </div>
                        <div style="clear:both;"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">&nbsp;</label>
                    <div class="inputs">
                        <div style="float:left;width:150px;height:150px;">
                            <div id="old_pic">
                                <img width='150' height='150'
                                    src="{{ asset('img/user_pic/' . ($user->photo ?: 'no_image.gif')) }}">
                            </div>
                        </div>
                        <div style="clear:both;"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">&nbsp;</label>
                    <div class="col-lg-6">
                        <button type="button" class="btn left-margin btn-cancel" onClick="goBack('/admin/users/index')">
                            {{'Return'}}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection