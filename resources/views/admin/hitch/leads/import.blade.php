@extends('admin.layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Bulk</span> -Lead Import
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="row">
            <form action="/admin/hitch/leads/import" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal"
                enctype="multipart/form-data">
                @csrf
                <div class="panel-body">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Dealer :<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="text" name="dealer_id" id="HitchLeadDealerId" class="required"
                                    style="width:100%">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">File :</label>
                            <div class="col-lg-9">
                                <input type="file" name="file" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Skip first row to import</label>
                            <div class="col-lg-1">
                                <input type="checkbox" name="skip" value="1" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="col-lg-2 control-label">&nbsp;</label>
                            <div class="col-lg-6">
                                <button type="submit" class="btn">Validate</button>
                                <button type="button" class="btn left-margin btn-cancel"
                                    onclick="goBack('/admin/hitch/leads/index')">Return</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("#frmadmin").validate();
        });
    </script>
    <script src="{{ legacy_asset('js/select2.js') }}"></script>

    <script type="text/javascript">
        function format(item) { return item.tag; }
        jQuery(document).ready(function () {
            jQuery("#HitchLeadDealerId").select2({
                data: { results: {}, text: 'tag' },
                formatSelection: format, formatResult: format,
                placeholder: "Select Dealer", minimumInputLength: 1,
                ajax: {
                    url: "{{ config('app.url') }}/admin/bookings/customerautocomplete",
                    dataType: "json", type: "GET",
                    data: function (params) { return { term: params, "is_dealer": true } },
                    processResults: function (data) {
                        return { results: $.map(data, function (item) { return { tag: item.tag, id: item.id } }) };
                    }
                }
            });
        });
    </script>
@endpush