@extends('admin.layouts.app')

@section('title', $title_for_layout ?? 'Metro Export')

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
                            <input type="text" name="Export[start]" id="ExportStart" value="{{ old('Export.start') }}" class="form-control" maxlength="10" placeholder="Start (MM-YYYY)" autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="Export[end]" id="ExportEnd" value="{{ old('Export.end') }}" class="form-control" maxlength="10" placeholder="End (MM-YYYY)" autocomplete="off">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary" title="EXPORT"><i class="icon-file-excel"></i> EXPORT</button>
                        </div>
                    </div>
                </div>
            </form>
            <div class="row">&nbsp;</div>
            <div class="table-responsive">
                <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                    <thead>
                        <tr>
                            @include('partials.dispacher.sortable_header', ['columns' => [
                                ['title' => '#', 'sortable' => false, 'style' => 'width: 5%;'],
                                ['title' => 'File', 'sortable' => false],
                                ['title' => 'Status', 'sortable' => false],
                                ['title' => 'Actions', 'sortable' => false, 'style' => 'width: 15%;']
                            ]])
                        </tr>
                    </thead>
                    <tbody>
                        @if($exports !== null && $exports->count() > 0)
                            @foreach($exports as $export)
                            
                                <tr>
                                    <td valign="top">{{ $export->id }}</td>
                                    <td valign="top">{{ $export->filename }}</td>
                                    <td valign="top">
                                        @if($export->status !== null && $export->status !== '')
                                            @if((string) $export->status === '0' || (int) $export->status === 0)
                                                Queued
                                            @elseif((string) $export->status === '1' || (int) $export->status === 1)
                                                Processing
                                            @elseif((string) $export->status === '2' || (int) $export->status === 2)
                                                <a href="/admin/metro_exports/download/{{ rawurlencode($export->filename) }}">Download</a>
                                            @else
                                                —
                                            @endif
                                        @else
                                            <a href="/admin/metro_exports/download/{{ rawurlencode($export->filename) }}">Download</a>
                                        @endif
                                    </td>
                                    <td class="action"></td>
                                </tr>
                            @endforeach
                            <tr><td height="6" colspan="4"></td></tr>
                        @else
                            <tr>
                                <td colspan="4" align="center">
                                    @if($exports === null)
                                        Metro exports table is not available.
                                    @else
                                        No record found
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            
            @include('partials.dispacher.paging_box', ['paginator' => $exports, 'limit' => $limit ?? 25])
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        $(function () {
            if (typeof $.fn.datetimepicker === 'function') {
                $('#ExportStart').datetimepicker({format: 'MM-YYYY'});
                $('#ExportEnd').datetimepicker({
                    useCurrent: false,
                    format: 'MM-YYYY'
                });
            }
        });
    </script>
    <style type="text/css">
        .datepicker .prev, .datepicker .next { background: none; }
    </style>
@endpush
