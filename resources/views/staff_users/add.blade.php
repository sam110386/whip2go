@extends('layouts.main')

@section('title', $listTitle)
@section('header_title', $listTitle)

@section('content')
    <div class="panel">
        <h2 style="margin-top:0;">{{ $listTitle }}</h2>
        @if(session('error'))<p style="color:red;">{{ session('error') }}</p>@endif

        @php
            $s = $staff ?? null;
            $oid = old('StaffUser.id', $s ? (string)$s->id : '');
        @endphp

        <form method="post" action="{{ $oid !== '' ? '/staff_users/add/' . base64_encode($oid) : '/staff_users/add' }}" enctype="multipart/form-data" class="form-horizontal">
            @csrf
            <input type="hidden" name="StaffUser[id]" value="{{ $oid }}">

            <div class="form-group">
                <label class="col-lg-2 control-label">First name <span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <input type="text" name="StaffUser[first_name]" class="form-control" maxlength="50" required
                           value="{{ old('StaffUser.first_name', optional($s)->first_name) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">Last name <span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <input type="text" name="StaffUser[last_name]" class="form-control" maxlength="50" required
                           value="{{ old('StaffUser.last_name', optional($s)->last_name) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">Email <span class="text-danger">*</span></label>
                <div class="col-lg-4">
                    <input type="email" name="StaffUser[email]" class="form-control" maxlength="50" required
                           value="{{ old('StaffUser.email', optional($s)->email) }}">
                </div>
            </div>

            @if(empty($oid))
                <div class="form-group">
                    <label class="col-lg-2 control-label">Phone # <span class="text-danger">*</span></label>
                    <div class="col-lg-4">
                        <input type="text" name="StaffUser[contact_number]" class="form-control" maxlength="10" required
                               value="{{ old('StaffUser.contact_number', '') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">Password <span class="text-danger">*</span></label>
                    <div class="col-lg-4">
                        <input type="password" name="StaffUser[pwd]" class="form-control" maxlength="50" required>
                    </div>
                </div>
            @else
                <div class="form-group">
                    <label class="col-lg-2 control-label">Password</label>
                    <div class="col-lg-4">
                        <input type="password" name="StaffUser[pwd]" class="form-control" maxlength="50" placeholder="Leave blank to keep">
                    </div>
                </div>
            @endif

            <div class="form-group">
                <label class="col-lg-2 control-label">Picture</label>
                <div class="col-lg-4">
                    <input type="file" name="StaffUser[photo]">
                </div>
            </div>

            @if($s && !empty($s->photo))
                <div class="form-group">
                    <label class="col-lg-2 control-label">Current</label>
                    <div class="col-lg-4">
                        <img width="120" height="120" src="{{ $userPicBase }}{{ $s->photo }}" alt="">
                    </div>
                </div>
            @endif

            <div class="form-group">
                <label class="col-lg-2 control-label">&nbsp;</label>
                <div class="col-lg-6">
                    <button type="submit" class="btn btn-primary">{{ $oid ? 'Update' : 'Save' }}</button>
                    <button type="button" class="btn" onclick="window.location.href='/staff_users/index'">Return</button>
                </div>
            </div>
        </form>
    </div>
@endsection
