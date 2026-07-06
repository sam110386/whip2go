@extends('admin.layouts.app')

@php
    $title ??= 'Update Insurance';
    $csorder ??= collect();
@endphp

@section('title', $title)

@section('content')

    <div class="panel">

        <div class="row">
            @includeif('partials.flash')
        </div>

        <div class="row">
            @if($csorder)
                <form id="ReportAdminUpdatefareForm" class="form-horizontal">
                    @csrf


                </form>
            @endif
        </div>
    </div>

@endsection

@push('scripts')


@endpush