@extends('admin.layouts.app')

@php
    $title ??= 'User Notes';
    $userid ??= '';
    $date_from ??= '';
    $date_to ??= '';
    $user ??= collect();
    $notelists ??= collect();
@endphp

@section('title', $title)

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <a href="{{ url('admin/users/index') }}">
                        <i class="icon-arrow-left52 position-left"></i>
                    </a>
                    <span class="text-semibold">User</span> - Notes
                </h4>
            </div>
            <div class="heading-elements">
                <a href="javascript:void(0)" class="btn left-margin" onclick="AddNewNote({{ $userid }})">
                    Add New Note
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="breadcrumb-line">
        <ul class="text-center pt-20 pb-10">
            <li>
                <h4>
                    <span class="text-semibold">User :</span>
                    {{ data_get($user, 'first_name', '') . ' ' . data_get($user, 'last_name', '')}}
                </h4>
            </li>
        </ul>
    </div>

    <div class="breadcrumb-line">
        <ul class="text-center">
            <li>
                <h6>
                    <span class="text-semibold">Notes History </span>
                </h6>
            </li>
        </ul>
    </div>

    <div class="panel">
        <div class="panel-body" id="postsPaging">
            <div id="listing">
                @include('admin.user_note.elements.index')
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/usernote/usernote.js') }}"></script>
@endpush