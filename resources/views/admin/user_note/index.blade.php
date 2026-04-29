@extends('admin.layouts.app')

@section('title', 'User Notes')

@php
    $userid ??= '';
    $user ??= null;
@endphp

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
                <a href="javascript:;" class="btn btn-primary" onclick="AddNewNote({{ $userid }})">
                    {{ 'Add New Note' }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title text-center">
                <span class="text-semibold">User :</span>
                {{ $user->first_name ?? '' }} {{ $user->last_name ?? '' }}
            </h5>
        </div>
        <div class="panel-body">
            <h6 class="text-center font-weight-semibold">Notes History</h6>
        </div>
    </div>

    <div class="panel">
        <div class="panel-body" id="postsPaging">
            <div id="listing">
                @include('admin.user_note._admin_index')
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content"></div>
        </div>
    </div>
@endsection

@push('styles')
    <style type="text/css">
        .table>thead>tr>th,
        .table>tbody>tr>th,
        .table>tfoot>tr>th,
        .table>thead>tr>td,
        .table>tbody>tr>td,
        .table>tfoot>tr>td {
            padding: 5px;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ legacy_asset('UserNote/js/usernote.js') }}"></script>
    <script src="{{ asset('js/admin_booking.js') }}"></script>
    <script type="text/javascript">
        function AddNewNote(userid) {
            $.ajax({
                url: "{{ url('admin/user_notes/add') }}",
                data: { userid: userid },
                success: function (data) {
                    $('#myModal .modal-content').html(data);
                    $('#myModal').modal('show');
                }
            });
        }

        function saveNote() {
            var formData = $('#addNoteForm').serialize();
            $.ajax({
                type: "POST",
                url: "{{ url('admin/user_notes/save') }}",
                data: formData,
                success: function (data) {
                    if (data.status) {
                        $('#myModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error saving note');
                    }
                }
            });
        }
    </script>
@endpush
