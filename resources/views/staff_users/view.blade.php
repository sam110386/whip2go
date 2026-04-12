@extends('layouts.main')

@section('title', $listTitle)
@section('header_title', $listTitle)

@section('content')
    <div class="panel">
        <h2 style="margin-top:0;">{{ $listTitle }}</h2>

        <div class="form-group">
            <label>First name</label>
            <div>{{ $staff->first_name }}</div>
        </div>
        <div class="form-group">
            <label>Last name</label>
            <div>{{ $staff->last_name }}</div>
        </div>
        <div class="form-group">
            <label>Email</label>
            <div>{{ $staff->email }}</div>
        </div>
        <div class="form-group">
            <label>Phone #</label>
            <div>{{ $staff->contact_number }}</div>
        </div>
        <div class="form-group">
            <label>Picture</label>
            <div>
                <img width="150" height="150" src="{{ $userPicBase }}{{ $staff->photo ?: 'no_image.gif' }}" alt="">
            </div>
        </div>

        <p><button type="button" class="btn" onclick="window.location.href='/staff_users/index'">Return</button></p>
    </div>
@endsection
