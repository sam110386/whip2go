@extends('layouts.admin')

@section('title', $title_for_layout ?? 'Metro Export')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Metro</span> - Export</h4>
            </div>
        </div>
    </div>
    <div class="row">
        @if(session('success'))
            <div class="col-md-12"><div class="alert alert-success">{{ session('success') }}</div></div>
        @endif
        @if(session('error'))
            <div class="col-md-12"><div class="alert alert-danger">{{ session('error') }}</div></div>
        @endif
    </div>
    <div class="panel">
        <div class="panel-body" id="listing">
            <form method="post" action="{{ $basePath }}/export" id="frmSearchadmin" name="frmSearchadmin">
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
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                <tr>
                    <th valign="top" width="5%">#</th>
                    <th valign="top">File</th>
                    <th valign="top">Status</th>
                    <th valign="top" width="15%">Actions</th>
                </tr>
                @if($exports !== null && $exports->count() > 0)
                    @foreach($exports as $export)
                        <tr>
                            <td valign="top">{{ $export->id }}</td>
                            <td valign="top">{{ $export->filename }}</td>
                            <td valign="top">
                                @if(property_exists($export, 'status') && $export->status !== null && $export->status !== '')
                                    @if((string) $export->status === '0' || (int) $export->status === 0)
                                        Queued
                                    @elseif((string) $export->status === '1' || (int) $export->status === 1)
                                        Processing
                                    @elseif((string) $export->status === '2' || (int) $export->status === 2)
                                        <a href="{{ $basePath }}/download/{{ rawurlencode($export->filename) }}">Download</a>
                                    @else
                                        —
                                    @endif
                                @else
                                    <a href="{{ $basePath }}/download/{{ rawurlencode($export->filename) }}">Download</a>
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
            </table>
            @if($exports !== null && $exports->total() > 0)
                <section class="pagging" style="margin-top:12px; overflow:hidden;">
                    <div style="width:40%; float:left;">
                        <form name="frmRecordsPages" action="{{ $basePath }}/index" method="get">
                            <label class="text-semibold">Show</label>
                            <select name="Record[limit]" class="textbox pagingcls form-control" style="display:inline-block; width:auto; min-width:70px;" onchange="this.form.submit()">
                                @foreach ([25,50,100,200] as $opt)
                                    <option value="{{ $opt }}" @selected((int)($limit ?? 25) === $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
                            <span>&nbsp;Records per page</span>
                        </form>
                    </div>
                    <div class="pull-right" style="margin-top:4px;">
                        {{ $exports->links() }}
                    </div>
                </section>
            @endif
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
