@extends('layouts.default')

@section('content')
    <div class="page-container login-container">
        <div class="page-content">
            <div class="content-wrapper">
                <div class="content">
                    <div class="panel panel-body login-form">
                        <div class="panel-body">
                            <div class="text-center">
                                @if(($status ?? 'error') === 'success')
                                    <div class="icon-object border-success text-success">
                                        <i class="icon-checkmark3"></i>
                                    </div>
                                @else
                                    <div class="icon-object border-danger text-danger">
                                        <i class="icon-cross3"></i>
                                    </div>
                                @endif
                                <h5 class="content-group-lg">
                                    @if (session('success'))
                                        <span class="text-success">{{ session('success') }}</span>
                                    @endif
                                    @if (session('error'))
                                        <span class="text-danger">{{ session('error') }}</span>
                                    @endif
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
