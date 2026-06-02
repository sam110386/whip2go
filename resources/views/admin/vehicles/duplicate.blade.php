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
                        <select name="Vehicle[user_id]" id="VehicleUserId" class="w-100" required></select>
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

    <script>

        (function () {
            var dealerId = @json($dealerid ?? null);
            var $sel = $('#vehicle_user_id');
            $sel.select2({
                placeholder: 'Select dealer',
                allowClear: true,
                minimumInputLength: 1,
                ajax: {
                    url: '/admin/bookings/customerautocomplete',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { term: params.term || '', is_dealer: true };
                    },
                    processResults: function (data) {
                        return {
                            results: (data || []).map(function (item) {
                                return { id: item.id, text: item.tag };
                            })
                        };
                    }
                }
            });
            if (dealerId) {
                $.getJSON('/admin/bookings/customerautocomplete', { id: dealerId })
                    .done(function (data) {
                        if (data && data.length) {
                            var item = data[0];
                            var opt = new Option(item.tag, item.id, true, true);
                            $sel.append(opt).trigger('change');
                        }
                    });
            }
        })();

    </script>

@endpush