@extends('admin.layouts.app')

@php
    $title ??= 'Update Profile';
    $user ??= collect();
    $states ??= [];
    $states['1110'] = 'Other';
    $selectedState = old('User.state_id', data_get($user, 'state_id'));
@endphp

@section('title', $title)

@section('content')

    <div class="row">
        @includeif('partials.flash')
    </div>

    <style>
        table.adminBox td {
            padding: 5px;
        }
    </style>

    <form method="POST" action="{{ url('/admin/admins/profile') }}" id="frmadmin" name="frmadmin" class="form-horizontal">
        @csrf

        <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" valign="top">
            <tr>
                <td valign="top">
                    <table align="center" width="98%" border="0" cellpadding="0" cellspacing="0">
                        <tr class="adminBoxHeading reportListingHeading">
                            <td class="adminGridHeading heading">
                                {{ $title }}
                            </td>
                            <td class="adminGridHeading"></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <table class="adminBox" border="0" cellpadding="2" cellspacing="2" width="100%">
                                    <tr height="20px">
                                        <td class="error_msg" colspan="4" align="left">
                                            Fields marked with an asterisk (*) are required.
                                        </td>
                                    </tr>

                                    <tr>
                                        @if(empty(data_get($user, 'id', '')))
                                            <td align="right" width="25%">
                                                <label class="control-label"><span class="error_msg">*</span> Username :</label>
                                            </td>
                                            <td>
                                                <input type="text" name="User[username]" value="{{ old('User.username') }}"
                                                    size="30" class="textbox-m form-control required">
                                                @error('User.username')
                                                    <span class="error_msg">{{ $message }}</span>
                                                @enderror
                                            </td>
                                        @else
                                            <td align="right" width="25%">
                                                <label class="control-label"><span class="error_msg">*</span> Username :</label>
                                            </td>
                                            <td>
                                                {{ data_get($user, 'username') }}
                                                <input type="hidden" name="User[username]"
                                                    value="{{ data_get($user, 'username') }}">
                                            </td>
                                        @endif
                                    </tr>

                                    <tr>
                                        <td align="right" width="25%">
                                            <label class="control-label"><span class="error_msg">*</span> First Name
                                                :</label>
                                        </td>
                                        <td>
                                            <input type="text" name="User[first_name]"
                                                value="{{ old('User.first_name', data_get($user, 'first_name')) }}"
                                                size="30" class="textbox-m form-control required">
                                            @error('User.first_name')
                                                <span class="error_msg"> {{ $message }} </span>
                                            @enderror
                                        </td>
                                    </tr>

                                    <tr>
                                        <td align="right" width="25%">
                                            <label class="control-label"><span class="error_msg">*</span> Last Name
                                                :</label>
                                        </td>
                                        <td>
                                            <input type="text" name="User[last_name]"
                                                value="{{ old('User.last_name', data_get($user, 'last_name')) }}" size="30"
                                                class="textbox-m form-control required">
                                            @error('User.last_name')
                                                <span class="error_msg">{{ $message }}</span>
                                            @enderror
                                        </td>
                                    </tr>

                                    <tr>
                                        <td align="right" width="25%">
                                            <label class="control-label"><span class="error_msg">*</span> Email Address
                                                :</label>
                                        </td>
                                        <td>
                                            <input type="email" name="User[email]"
                                                value="{{ old('User.email', data_get($user, 'email')) }}" size="30"
                                                class="textbox-m form-control required">
                                            @error('User.email')
                                                <span class="error_msg">{{ $message }}</span>
                                            @enderror
                                        </td>
                                    </tr>

                                    @if(!data_get($user, 'id'))
                                        <tr>
                                            <td align="right">
                                                <label class="control-label"><span class="error_msg">*</span> Password :</label>
                                            </td>
                                            <td>
                                                <input type="password" name="User[npwd]" id="password"
                                                    class="textbox-m form-control required" value="">
                                                @error('User.npwd')
                                                    <span class="error_msg">{{ $message }}</span>
                                                @enderror
                                            </td>
                                        </tr>

                                        <tr>
                                            <td align="right">
                                                <label class="control-label"><span class="error_msg">*</span> Confirm Password
                                                    :</label>
                                            </td>
                                            <td>
                                                <input type="password" name="User[conpwd]" size="30" maxlength="40"
                                                    class="textbox-m form-control" value="">
                                                @error('User.conpwd')
                                                    <span class="error_msg">{{ $message }}</span>
                                                @enderror
                                            </td>
                                        </tr>
                                    @endif

                                    <tr>
                                        <td align="right" width="25%">
                                            <label class="control-label"><span class="error_msg">*</span> Address 1
                                                :</label>
                                        </td>
                                        <td>
                                            <input type="text" name="User[address1]"
                                                value="{{ old('User.address1', data_get($user, 'address1')) }}" size="30"
                                                class="textbox-m form-control required">
                                            @error('User.address1')
                                                <span class="error_msg">{{ $message }}</span>
                                            @enderror
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right" width="25%">
                                            <label class="control-label">Address 2 :</label>
                                        </td>
                                        <td>
                                            <input type="text" name="User[address2]"
                                                value="{{ old('User.address2', data_get($user, 'address2')) }}" size="30"
                                                class="textbox-m form-control required">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right" width="25%">
                                            <label class="control-label"><span class="error_msg">*</span> City :</label>
                                        </td>
                                        <td>
                                            <input type="text" name="User[city]"
                                                value="{{ old('User.city', data_get($user, 'city')) }}" size="30"
                                                class="textbox-m form-control required">
                                            @error('User.city')
                                                <span class="error_msg">{{ $message }}</span>
                                            @enderror
                                        </td>
                                    </tr>

                                    <tr>
                                        <td align="right" width="25%">
                                            <label class="control-label"><span class="error_msg">*</span> State :</label>
                                        </td>
                                        <td width="50%">
                                            <div id="StateDiv">
                                                <select name="User[state_id]" class="textbox-m form-control required"
                                                    onChange="return showOtherState()">
                                                    <option value="">Please select..</option>
                                                    @foreach($states as $key => $name)
                                                        <option value="{{ $key }}" {{ $selectedState == $key ? 'selected' : '' }}>
                                                            {{ $name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('User.state_id')
                                                <span class="error_msg">{{ $message }}</span>
                                            @enderror
                                        </td>
                                    </tr>

                                    <tr id="showOtherStateBox"
                                        style="{{ old('User.state_id', data_get($user, 'state_id')) != '1110' ? 'display:none' : '' }}">
                                        <td align="right" width="25%">
                                            <label class="control-label">State Name :</label>
                                        </td>
                                        <td>
                                            <input type="text" name="User[other_state]"
                                                value="{{ old('User.other_state', data_get($user, 'other_state')) }}"
                                                class="textbox-m form-control">
                                        </td>
                                    </tr>

                                    <tr>
                                        <td align="right" width="25%">
                                            <label class="control-label"><span class="error_msg">*</span> Fax Number
                                                :</label>
                                        </td>
                                        <td>
                                            <input type="text" name="User[fax1][0]"
                                                value="{{ old('User.fax1.0', data_get($user, 'fax1.0', '')) }}"
                                                maxlength="3" class="textbox-phone form-control number"
                                                style="display: inline-block; width: auto;" id="number1">
                                            <input type="text" name="User[fax1][1]"
                                                value="{{ old('User.fax1.1', data_get($user, 'fax1.1', '')) }}"
                                                maxlength="3" class="textbox-phone form-control number"
                                                style="display: inline-block; width: auto;" id="number2">
                                            <input type="text" name="User[fax1][2]"
                                                value="{{ old('User.fax1.2', data_get($user, 'fax1.2', '')) }}"
                                                maxlength="4" class="textbox-phone form-control required"
                                                style="display: inline-block; width: auto;" id="number3">
                                        </td>
                                    </tr>

                                    <tr>
                                        <td align="right" width="25%">
                                            <label class="control-label"><span class="error_msg">*</span>Phone :</label>
                                        </td>
                                        <td>
                                            <input type="text" name="User[phone1][0]"
                                                value="{{ old('User.phone1.0', data_get($user, 'phone1.0', '')) }}"
                                                maxlength="3" class="textbox-phone form-control number"
                                                style="display: inline-block; width: auto;" id="number4">
                                            <input type="text" name="User[phone1][1]"
                                                value="{{ old('User.phone1.1', data_get($user, 'phone1.1', '')) }}"
                                                maxlength="3" class="textbox-phone form-control number"
                                                style="display: inline-block; width: auto;" id="number5">
                                            <input type="text" name="User[phone1][2]"
                                                value="{{ old('User.phone1.2', data_get($user, 'phone1.2', '')) }}"
                                                maxlength="4" class="textbox-phone form-control required"
                                                style="display: inline-block; width: auto;" id="number6">
                                        </td>
                                    </tr>

                                    <tr>
                                        <td align="right">
                                            <label class="control-label"><span class="error_msg"></span> Status :</label>
                                        </td>
                                        <td>
                                            <select name="User[status]" class="textbox-s form-control" style="width: auto;">
                                                <option value="">Select..</option>
                                                <option value="0" @selected(old('User.status', data_get($user, 'status')) == 0)>
                                                    Inactive
                                                </option>
                                                <option value="1" @selected(old('User.status', data_get($user, 'status')) == 1)>
                                                    Active
                                                </option>
                                            </select>
                                            @error('User.status')
                                                <span class="error_msg">{{ $message }}</span>
                                            @enderror
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>&nbsp;</td>
                                    </tr>

                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>
                                            @if(!empty(data_get($user, 'id')))
                                                <button type="submit" class="btn_53 btn btn-primary">Update</button>
                                            @else
                                                <button type="submit" class="btn_53 btn btn-primary">Save</button>
                                            @endif
                                            <button type="button" class="btn_53 btn btn-default"
                                                onClick="goBack('/admin/admins/index')">
                                                Cancel
                                            </button>
                                        </td>
                                        <td>&nbsp;</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <input type="hidden" name="User[id]" value="{{ data_get($user, 'id', '') }}">
    </form>

@endsection


@push('scripts')
    <script src="{{ legacy_asset('js/prototype.js') }}"></script>
    <script src="{{ legacy_asset('js/jquery.autotab.js') }}"></script>

    <script>
        jQuery(document).ready(function () {
            jQuery("#frmadmin").validate();
            jQuery('#number1, #number2, #number3').autotab_magic().autotab_filter('numeric');
            jQuery('#number4, #number5, #number6').autotab_magic().autotab_filter('numeric');
        });
    </script>

@endpush