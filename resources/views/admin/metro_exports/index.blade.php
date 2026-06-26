@extends('admin.layouts.app')

@php
    $title ??= 'Metro Export';
    $exports ??= [];
    $limit ??= 50;
@endphp

@section('title', $title)

@push('styles')
    <style type="text/css">
        .datepicker .prev,
        .datepicker .next {
            background: none;
        }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Metro</span> - Export
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body" id="listing">
            <form method="post" action="{{ url('/admin/metro_exports/export')}}" id="frmSearchadmin" name="frmSearchadmin">
                @csrf
                <div class="row">
                    <div class="col-md-10">
                        <div class="col-md-3">
                            <input type="text" name="Export[start]" id="ExportStart" value="{{ old('Export.start') }}"
                                class="form-control">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="Export[end]" id="ExportEnd" value="{{ old('Export.end') }}"
                                class="form-control">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary" title="EXPORT">
                                <i class="icon-file-excel"></i> EXPORT
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div class="table-responsive">
                <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                    <thead>
                        <tr>
                            <th valign="top" width="5%">#</th>
                            <th valign="top">File</th>
                            <th valign="top">Status</th>
                            <th valign="top" width="15%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($exports as $export)
                            <tr>
                                <td valign="top">
                                    {{ data_get($export, 'id', '') }}
                                </td>
                                <td valign="top">
                                    {{ data_get($export, 'filename', '') }}
                                </td>
                                <td valign="top">
                                    @if(data_get($export, 'status') == 0)
                                        Queued
                                    @elseif(data_get($export, 'status') == 1)
                                        Processing
                                    @elseif(data_get($export, 'status') == 2)
                                        <a href="{{ url('admin/metro_exports/download/' . data_get($export, 'filename', '')) }}">
                                            Download
                                        </a>
                                    @endif
                                </td>
                                <td class="action"></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" align="center">
                                    No record found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('partials.dispacher.paging_box', ['paginator' => $exports, 'limit' => $limit])
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        $(function () {
            if (typeof $.fn.datetimepicker === 'function') {
                $('#ExportStart').datetimepicker({ format: 'MM-YYYY' });
                $('#ExportEnd').datetimepicker({
                    useCurrent: false,
                    format: 'MM-YYYY'
                });
            }
        });
    </script>
@endpush