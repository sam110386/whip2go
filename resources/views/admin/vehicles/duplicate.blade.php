@extends('admin.layouts.app')

@section('title', 'Duplicate Vehicle')

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
@endpush

@section('content')

    <form method="POST" action="{{ url('/admin/vehicles/duplicate/' . base64_encode($vehicleid)) }}"
        id="vehicle-duplicate-form" name="vehicle-duplicate-form" class="form-horizontal">
        @csrf

        <div class="page-header">
            <div class="page-header-content">
                <div class="page-title">
                    <h4>
                        <i class="icon-arrow-left52 position-left"></i>
                        <span class="text-semibold">Duplicate</span> Vehicle
                    </h4>
                </div>
            </div>
        </div>

        <div class="row">
            @includeif('partials.flash')
        </div>

        <div class="panel">
            <div class="panel-body">
                <legend class="text-size-large text-bold">VIN Details</legend>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        VIN Number :<span class="text-danger">*</span>
                    </label>
                    <div class="col-lg-4">
                        <input type="text" name="Vehicle[vin_no]" class="form-control text-uppercase" required
                            maxlength="100">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Owner :<span class="text-danger">*</span>
                    </label>
                    <div class="col-lg-4">
                        <input type="text" name="Vehicle[user_id]" id="VehicleUserId" class="w-100 required" required>
                    </div>
                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary full-width">
                            Proceed <i class="icon-arrow-right14 position-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/select2.js') }}"></script>

    <script type="text/javascript">

        function format(item) {
            return item.tag;
        }

        jQuery(document).ready(function () {

            jQuery("#VehicleUserId").select2({
                data: { results: {}, text: 'tag' },
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Dealer",
                minimumInputLength: 1,
                ajax: {
                    url: @json(url('/admin/bookings/customerautocomplete')),
                    dataType: "json",
                    type: "GET",
                    data: function (params) {
                        return { term: params, "is_dealer": true }
                    },
                    processResults: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return { tag: item.tag, id: item.id }
                            })
                        };
                    }
                },
                initSelection: function (element, callback) {
                    var dealer_id = @json($dealerid ?? null);
                    if (dealer_id.length > 0) {
                        jQuery.ajax({
                            url: @json(url('/admin/bookings/customerautocomplete')),
                            dataType: "json",
                            type: "GET",
                            data: { "id": dealer_id }
                        }).done(function (data) {
                            callback(data[0]);
                        });
                    }
                }
            });
        });

    </script>

@endpush