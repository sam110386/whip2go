@extends('layouts.admin')

@section('title', $listTitle)

@section('content')
    <div class="row">
        @include('layouts.flash-messages')
    </div>

    <form action="{{ url('admin/users/add' . ($user?->id ? '/' . base64_encode($user?->id) : '')) }}" method="POST"
        name="frmadmin" id="frmadmin" class="form-horizontal" enctype="multipart/form-data">
        @csrf

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
                    <button type="submit" class="btn btn-primary">
                        {{ empty($user?->id) ? 'Save Profile' : 'Update Profile' }}
                    </button>
                    <button type="button" class="btn left-margin btn-danger" onClick="goBack('/admin/users/index')">
                        {{ 'Return' }}
                    </button>
                </div>
            </div>
        </div>

        <div class="masonry">
            <div class="item">
                <legend class="text-size-large text-bold">
                    {{'Account Details'}}
                </legend>
                <div class="form-group">
                    <label class="col-lg-4 control-label">
                        {{'First Name :'}}
                        <span class="text-danger">{{'*'}}</span>
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="User[first_name]" maxlength="50" class="form-control required"
                            value="{{ old('User.first_name', $user?->first_name) }}">
                        @error('User.first_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">
                        {{'Last Name :'}}
                        <span class="text-danger">{{'*'}}</span>
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="User[last_name]" maxlength="50" class="form-control required"
                            value="{{ old('User.last_name', $user?->last_name) }}">
                        @error('User.last_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">
                        {{'Email :'}}
                        <span class="text-danger">{{'*'}}</span>
                    </label>
                    <div class="col-lg-8">
                        <input type="email" name="User[email]" class="email form-control required"
                            value="{{ old('User.email', $user?->email) }}">
                        @error('User.email')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">
                        {{'Notification Email :'}}
                    </label>
                    <div class="col-lg-8">
                        <input type="email" name="User[notify_email]" class="form-control"
                            value="{{ old('User.notify_email', $user?->notify_email) }}">
                    </div>
                </div>
                @if (empty($user?->id))
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'Phone #'}}
                            <span class="text-danger">{{'*'}}</span>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="User[contact_number]" size="10" maxlength="10"
                                class="form-control required digits"
                                value="{{ old('User.contact_number', $user?->contact_number) }}">
                            @error('User.contact_number')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                @endif
                <div class="form-group">
                    <label class="col-lg-4 control-label">
                        {{'Address :'}}
                        <span class="text-danger">{{'*'}}</span>
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="User[address]" maxlength="150" class="form-control required"
                            value="{{ old('User.address', $user?->address) }}">
                        @error('User.address')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">
                        {{'City :'}}
                        <span class="text-danger">{{'*'}}</span>
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="User[city]" maxlength="50" class="form-control required"
                            value="{{ old('User.city', $user?->city) }}">
                        @error('User.city')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">
                        {{'State :'}}
                        <span class="text-danger">{{'*'}}</span>
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="User[state]" maxlength="2" class="form-control required"
                            value="{{ old('User.state', $user?->state) }}">
                        @error('User.state')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">
                        {{'Postal Code :'}}
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="User[zip]" maxlength="10" class="form-control"
                            value="{{ old('User.zip', $user?->zip) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label">
                        {{'Password :'}}
                        @if (empty($user?->id))
                            <span class="text-danger">{{'*'}}</span>
                        @endif
                    </label>
                    <div class="col-lg-8">
                        <input type="password" name="User[pwd]" maxlength="50"
                            class="form-control @if (empty($user?->id)) required @endif">
                        @error('User.pwd')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label">
                        {{'Booking Auto Renew :'}}
                    </label>
                    <div class="col-lg-8">
                        <select name="User[auto_renew]" class="form-control">
                            <option value="1" @selected(old('User.auto_renew', $user?->auto_renew) == 1)>
                                {{'Active'}}
                            </option>
                            <option value="0" @selected(old('User.auto_renew', $user?->auto_renew) == 0)>
                                {{'Inactive'}}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">
                        {{'Currency :'}}
                    </label>
                    <div class="col-lg-8">
                        <select name="User[currency]" class="form-control">
                            @foreach ($currencies as $code => $name)
                                <option value="{{ $code }}" @selected(old('User.currency', $user?->currency) == $code)>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @if ($user?->is_dealer)
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Distance Unit :</label>
                        <div class="col-lg-8">
                            <select name="User[distance_unit]" class="form-control">
                                <option value="Mi" @selected(old('User.distance_unit', $user?->distance_unit) == 'Mi')>Miles
                                </option>
                                <option value="KM" @selected(old('User.distance_unit', $user?->distance_unit) == 'KM')>KM</option>
                            </select>
                        </div>
                    </div>
                @endif
            </div>

            @if (!empty($user?->id) && $user?->is_dealer != 1)
                <div class="item">
                    <legend class="text-size-large text-bold">
                        {{'License Details'}}
                    </legend>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'First Name :'}}
                            <span class="text-danger">{{'*'}}</span>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="UserLicenseDetail[givenName]" maxlength="50" class="form-control required"
                                value="{{ old('UserLicenseDetail.givenName', $user?->userLicenseDetail->givenName ?? '') }}">
                            @error('UserLicenseDetail.givenName')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'Last Name :'}}
                            <span class="text-danger">{{'*'}}</span>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="UserLicenseDetail[lastName]" maxlength="50" class="form-control required"
                                value="{{ old('UserLicenseDetail.lastName', $user?->userLicenseDetail->lastName ?? '') }}">
                            @error('UserLicenseDetail.lastName')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'Date Of Birth :'}}
                        </label>
                        <div class="col-lg-8">
                            <input type="text" id="UserLicenseDetailDateOfBirth" name="UserLicenseDetail[dateOfBirth]"
                                maxlength="10" class="form-control"
                                value="{{ old('UserLicenseDetail.dateOfBirth', $user?->userLicenseDetail->dateOfBirth ?? '') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'Address :'}}
                            <span class="text-danger">{{'*'}}</span>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="UserLicenseDetail[addressStreet]" maxlength="150"
                                class="form-control required"
                                value="{{ old('UserLicenseDetail.addressStreet', $user?->userLicenseDetail->addressStreet ?? '') }}">
                            @error('UserLicenseDetail.addressStreet')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'City :'}}
                            <span class="text-danger">{{'*'}}</span>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="UserLicenseDetail[addressCity]" maxlength="50"
                                class="form-control required"
                                value="{{ old('UserLicenseDetail.addressCity', $user?->userLicenseDetail->addressCity ?? '') }}">
                            @error('UserLicenseDetail.addressCity')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'State :'}}
                            <span class="text-danger">{{'*'}}</span>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="UserLicenseDetail[addressState]" maxlength="2"
                                class="form-control required"
                                value="{{ old('UserLicenseDetail.addressState', $user?->userLicenseDetail->addressState ?? '') }}">
                            @error('UserLicenseDetail.addressState')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'Postal Code :'}}
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="UserLicenseDetail[addressPostalCode]" maxlength="10" class="form-control"
                                value="{{ old('UserLicenseDetail.addressPostalCode', $user?->userLicenseDetail->addressPostalCode ?? '') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'License # :'}}
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="User[licence_number]" maxlength="50" class="form-control"
                                value="{{ old('User.licence_number', $user?->licence_number ? \App\Helpers\Legacy\Security::decrypt($user?->licence_number) : '') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'License Exp Date :'}}
                        </label>
                        <div class="col-lg-8">
                            <input type="text" id="UserLicenceExpDate" name="User[licence_exp_date]" maxlength="10"
                                class="date form-control" value="{{ old('User.licence_exp_date', $user?->licence_exp_date) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'License State :'}}
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="User[licence_state]" maxlength="2" class="form-control"
                                value="{{ old('User.licence_state', $user?->licence_state) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'License Type :'}}
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="User[licence_type]" maxlength="50" class="form-control"
                                value="{{ old('User.licence_type', $user?->licence_type) }}">
                        </div>
                    </div>
                </div>

                <div class="item">
                    <legend class="text-size-large text-bold">
                        {{"Documents"}}
                    </legend>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">
                            {{"License Picture 1:"}}
                        </label>
                        <div class="inputs">
                            <div class="input-cont">
                                <input type="file" name="tmp_doc_1">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="inputs">
                            <div style="float:left;width:150px;height:150px;">
                                <div id="old_pic">
                                    <img width='150' height='150'
                                        src="{{ asset('files/userdocs/' . ($user?->license_doc_1 ?? 'no_image.gif')) }}">
                                </div>
                            </div>
                            <div style="clear:both;"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">
                            {{'License Picture 2 :'}}
                        </label>
                        <div class="inputs">
                            <div class="input-cont">
                                <input type="file" name="tmp_doc_2">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="inputs">
                            <div style="float:left;width:150px;height:150px;">
                                <div id="old_pic">
                                    <img width='150' height='150'
                                        src="{{ asset('files/userdocs/' . ($user?->license_doc_2 ?? 'no_image.gif')) }}">
                                </div>
                            </div>
                            <div style="clear:both;"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">
                            {{'Picture :'}}
                        </label>
                        <div class="inputs">
                            <div class="input-cont">
                                <input type="file" name="User[photo]">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="inputs">
                            <div style="float:left;width:150px;height:150px;">
                                <div id="old_pic">
                                    <img width='150' height='150'
                                        src="{{ asset('img/user_pic/' . ($user?->photo ?? 'no_image.gif')) }}">
                                </div>
                            </div>
                            <div style="clear:both;"></div>
                        </div>
                    </div>

                </div>
                <div class="item">
                    <legend class="text-size-large text-bold">
                        {{'License Details'}}
                    </legend>
                    <div class="form-group">
                        <label class="col-lg-3 control-label">
                            {{'Update License Data :'}}
                        </label>
                        <div class="col-lg-1">
                            <input type="checkbox" name="User[updatelicense]" class="form-control" value="1">
                        </div>
                    </div>
                </div>
            @endif

            @if (!empty($user?->id) && $user?->is_dealer == 1)
                <div class="item">
                    <legend class="text-size-large text-bold">
                        {{'Company Details'}}
                    </legend>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'Company Name :'}}
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="User[company_name]" maxlength="140" class="form-control"
                                value="{{ old('User.company_name', $user?->company_name) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'Company Address :'}}
                            <span class="text-danger">{{"*"}}</span>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="User[company_address]" maxlength="150" class="form-control required"
                                value="{{ old('User.company_address', $user?->company_address) }}">
                            @error('User.company_address')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'Company City :'}}
                            <span class="text-danger">{{"*"}}</span>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="User[company_city]" maxlength="50" class="form-control required"
                                value="{{ old('User.company_city', $user?->company_city) }}">
                            @error('User.company_city')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'Company State :'}}
                            <span class="text-danger">{{"*"}}</span>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="User[company_state]" maxlength="2" class="form-control required"
                                value="{{ old('User.company_state', $user?->company_state) }}">
                            @error('User.company_state')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'Company ZIP :'}}
                            <span class="text-danger">{{"*"}}</span>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="User[company_zip]" maxlength="10" class="form-control required"
                                value="{{ old('User.company_zip', $user?->company_zip) }}">
                            @error('User.company_zip')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'Country :'}}
                            <span class="text-danger">{{"*"}}</span>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="User[company_country]" maxlength="20" class="form-control required"
                                value="{{ old('User.company_country', $user?->company_country) }}">
                            @error('User.company_country')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'Representative Name :'}}
                            <span class="text-danger">{{"*"}}</span>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="User[representative_name]" maxlength="40" class="form-control"
                                value="{{ old('User.representative_name', $user?->representative_name) }}">
                            @error('User.representative_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'Representative Role :'}}
                            <span class="text-danger">{{"*"}}</span>
                        </label>
                        <div class="col-lg-8">
                            <input type="text" name="User[representative_role]" maxlength="30" class="form-control"
                                value="{{ old('User.representative_role', $user?->representative_role) }}">
                            @error('User.representative_role')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">
                            {{'Representative Signature: '}}
                        </label>
                        <div class="inputs">
                            <div class="input-cont">
                                <input type="file" name="representative_sign">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="inputs">
                            <div style="float:left;width:150px;height:150px;">
                                <div id="old_pic">
                                    <img width='150' height='150'
                                        src="{{ asset('files/userdocs/' . ($user?->representative_sign ?? 'no_image.gif')) }}">
                                </div>
                            </div>
                            <div style="clear:both;"></div>
                        </div>
                    </div>


                    <div class="form-group">
                        <label class="col-lg-2 control-label">
                            {{'Picture :'}}
                        </label>
                        <div class="inputs">
                            <div class="input-cont">
                                <input type="file" name="User[photo]">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="inputs">
                            <div style="float:left;width:150px;height:150px;">
                                <div id="old_pic">
                                    <img width='150' height='150'
                                        src="{{ asset('img/user_pic/' . ($user?->photo ?? 'no_image.gif')) }}">
                                </div>
                            </div>
                            <div style="clear:both;"></div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label">&nbsp;</label>
            <div class="col-lg-6">
                <button type="submit" class="btn btn-primary">
                    {{ empty($user?->id) ? 'Save Profile' : 'Update Profile' }}
                </button>
                <button type="button" class="btn left-margin btn-danger" onClick="goBack('/admin/users/index')">
                    {{'Return'}}
                </button>
            </div>
        </div>

        <input type="hidden" name="User[is_dealer]" value="{{ old('User.is_dealer', $user?->is_dealer) }}">
        <input type="hidden" name="User[id]" value="{{ $user?->id }}">
        <input type="hidden" name="UserLicenseDetail[id]" value="{{ $user?->userLicenseDetail?->id }}">
    </form>
@endsection

@push('scripts')
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("#UserLicenseDetailDateOfBirth,#UserLicenceExpDate").datepicker({
                dateFormat: 'mm/dd/yy',
                changeMonth: true,
                changeYear: true
            });

            jQuery("#frmadmin").validate();
        });
    </script>
@endpush