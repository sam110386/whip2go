@extends('admin.layouts.app')
@section('title', 'Past Due - Reports')
@section('content')
<script src="{{ legacy_asset('js/select2.js') }}"></script>
<link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
<script type="text/javascript">
    function format(item) {
        return item.tag;
    }
    jQuery(document).ready(function() {
        jQuery("#SearchDealerid").select2({
            data: {results: {}, text: 'tag'},
            formatSelection: format,
            formatResult: format,
            placeholder: "Select Customer ",
            minimumInputLength: 1,
            ajax: {
                url: "{{ config('app.url') }}/admin/bookings/customerautocomplete",
                dataType: "json",
                type: "GET",
                data: function (params) {
                    return {term: params,"is_dealer":true}
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
                var dealer_id = "{{ $dealerid ?? '' }}";
                if (dealer_id.length > 0) {
                    jQuery.ajax({
                        url: "{{ config('app.url') }}/admin/bookings/customerautocomplete",
                        dataType: "json",
                        type: "GET",
                        data: {"id": dealer_id}
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
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Past Due</span> - Reports</h4>
        </div>
    </div>
</div>
<div class="row">
    @includeif('partials.flash')
</div>

<div class="panel">
    <form method="POST" action="{{ url('/admin/report/pastdues') }}" class="form-horizontal">
        @csrf
        <div class="panel-body">
            <div class="col-md-2">
                <input type="text" name="Search[dealerid]" id="SearchDealerid" style="width:100%;" value="{{ old('Search.dealerid', $dealerid ?? '') }}" placeholder="Dealers">
            </div>
            <div class="col-md-2">
                <button type="submit" name="search" value="search" class="btn btn-primary">SEARCH</button>
            </div>
        </div>
    </form>
</div>

<div class="panel">
    <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
        @include('admin.report.elements.admin_pastdue')
    </div>
</div>

<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</div>
<script src="{{ asset('js/report/report.js') }}"></script>
@endsection
