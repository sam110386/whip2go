@extends('admin.layouts.app')
@section('title', 'Report - Cash Flow')
@section('content')
<script src="{{ asset('js/report/papaparser.js') }}"></script>
<script src="{{ asset('js/report/excellentexport.js') }}"></script>
<script src="{{ asset('js/select2.js') }}"></script>
<link rel="stylesheet" href="{{ asset('css/select2.css') }}">
<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery("body").addClass('sidebar-xs');
    });
</script>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Report</span> - Cash Flow</h4>
        </div>
    </div>
</div>
<div class="row ">
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
</div>
<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ url('/admin/report/cashflow') }}" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
            @csrf
            <div class="row pb-10">
                <div class="col-md-12">
                    <div class="col-md-4">
                        <input type="text" name="Search[user_id]" id="SearchUserId" value="{{ old('Search.user_id', $user_id ?? '') }}" class="focus_text textfield" placeholder="Select Dealer.." style="width:100%;">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="search" value="search" class="btn btn-primary">APPLY</button>
                    </div>
                    <div class="col-md-2">
                        <a href="#" download="cashflow.csv" class="btn btn-primary" onclick="return ExcellentExport.csv(this, 'portfolio');">Export</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="panel">
    <div class="panel-body" id="listing">
        @if(!empty($vehicles))
            @include('admin.report.elements._cashflow')
            <style type="text/css">
                .table.panel{border-color:unset;border: none;}
            </style>
        @else
            <table width="100%" cellpadding="1" cellspacing="1" border="0" class="borderTable">
                <tr>
                    <td colspan="7" align="center">No record found</td>
                </tr>
            </table>
        @endif
    </div>
</div>
<script type="text/javascript">
    function format(item) {
        return item.tag;
    }
    jQuery(document).ready(function() {
        jQuery("#SearchUserId").select2({
            data: {
                results: {},
                text: 'tag'
            },
            formatSelection: format,
            formatResult: format,
            placeholder: "Select Customer ",
            minimumInputLength: 1,
            ajax: {
                url: "{{ config('app.url') }}/admin/bookings/customerautocomplete",
                dataType: "json",
                type: "GET",
                data: function(params) {
                    return {
                        term: params,
                        is_dealer: true
                    }
                },
                processResults: function(data) {
                    return {
                        results: jQuery.map(data, function(item) {
                            return {
                                tag: item.tag,
                                id: item.id
                            }
                        })
                    };
                }
            },
            initSelection: function(element, callback) {
                var id = $(element).val();
                if (id !== "") {
                    $.ajax("{{ config('app.url') }}/admin/bookings/customerautocomplete", {
                        dataType: "json",
                        type: 'GET',
                        data: {
                            id: id
                        }
                    }).done(function(data) {
                        callback(data[0]);
                    });
                }
            }
        });
    });
</script>
@endsection
