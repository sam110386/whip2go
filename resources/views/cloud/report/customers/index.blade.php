@extends('layouts.admin')
@section('title', 'Customer - Cash Flow')
@section('content')
<script src="{{ asset('js/select2.js') }}"></script>
<link rel="stylesheet" href="{{ asset('css/select2.css') }}">
<script type="text/javascript">
    function format(item) {
        return item.tag;
    }
    jQuery(document).ready(function() {
        jQuery("#SearchRenterid").select2({
            data: {results: {}, text: 'tag'},
            formatSelection: format,
            formatResult: format,
            placeholder: "Select Customer ",
            minimumInputLength: 1,
            ajax: {
                url: "{{ config('app.url') }}/cloud/linked_reports/customerautocomplete",
                dataType: "json",
                type: "GET",
                data: function (params) {
                    return {term: params}
                },
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {tag: item.tag,id: item.id}
                        })
                    };
                }
            },
            initSelection: function (element, callback) {
                var renter_id = "{{ $renterid ?? '' }}";
                if (renter_id.length > 0) {
                    jQuery.ajax({
                        url: "{{ config('app.url') }}/cloud/linked_reports/customerautocomplete",
                        dataType: "json",
                        type: "GET",
                        data: {"renter_id": renter_id}
                    }).done(function (data) {
                        callback(data[0]);
                    });
                }
            }
        });
    });
</script>
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</div>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Customer</span> - Cash Flow</h4>
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
    <form method="POST" action="{{ url('/cloud/report/customers') }}" class="form-horizontal">
        @csrf
        <div class="panel-body">
            <div class="col-md-2">
                <select name="Search[dealerid]" class="form-control md-form">
                    <option value="">Dealer</option>
                    @foreach ($dealers ?? [] as $id => $label)
                        <option value="{{ $id }}" @selected(old('Search.dealerid', $dealerid ?? '') == $id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="Search[renterid]" id="SearchRenterid" style="width:100%;" value="{{ old('Search.renterid', $renterid ?? '') }}" placeholder="Driver">
            </div>
            <div class="col-md-2">
                <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ old('Search.keyword', $keyword ?? '') }}" placeholder="Booking#">
            </div>
            <div class="col-md-2">
                <button type="submit" name="search" value="search" class="btn btn-primary">SEARCH</button>
            </div>
        </div>
    </form>
</div>

<div class="panel">
    <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
        @include('cloud.report.elements.cloud_index')
    </div>
</div>
<script src="{{ asset('js/report/report.js') }}"></script>
@endsection
