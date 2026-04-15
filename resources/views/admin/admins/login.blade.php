@extends('layouts.layout_admin')

@section('title', 'Admin Login')

@section('content')
    <div class="panel panel-body login-form">

        <div class="row">
            @include('partials.flash')
        </div>

        <div class="text-center">
            <div class="icon-object border-slate-300 text-slate-300">
                <i class="icon-reading"></i>
            </div>
            <h5 class="content-group">
                {{ 'Administrator Login' }}
            </h5>
        </div>
        <form action="{{ url('/admin/admins/login') }}" method="post" name="myForm"
            class="form-horizontal form-without-legend">
            @csrf
            <div class="form-group">
                <div class="col-lg-12">
                    <input type="text" name="username" class="form-control" id="username" placeholder="Username" required>
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-12">
                    <input type="password" name="password" class="form-control" id="password" placeholder="Password"
                        required />
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8 col-md-offset-4 padding-left-0 padding-top-20">
                    <button type="submit" class="btn btn-primary">
                        {{ 'Login as Administrator' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection