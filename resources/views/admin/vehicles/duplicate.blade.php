@extends('admin.layouts.app')

@section('title', 'Duplicate Vehicle')

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
@endpush

@section('content')
    @php
        $base = $vehicleBasePath ?? '/admin/vehicles';
        $returnUrl = $returnListUrl ?? ($base . '/index');
    @endphp

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Duplicate</span> Vehicle
                </h4>
            </div>
            <div class="heading-elements">
                <div class="heading-btn-group">
                    <button type="submit" form="vehicle-duplicate-form" class="btn btn-primary">
                        Proceed <i class="icon-arrow-right14 position-right"></i>
                    </button>
                    <a href="{{ $returnUrl }}" class="btn btn-default">Cancel</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <form method="POST" action="{{ $base }}/duplicate/{{ base64_encode((string)($vehicleid ?? 0)) }}"
          id="vehicle-duplicate-form" name="vehicle-duplicate-form" class="form-horizontal">
        @csrf

        <div class="panel panel-flat">
            <div class="panel-heading">
                <h5 class="panel-title">VIN Details</h5>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">VIN Number :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[vin_no]" class="form-control text-uppercase"
                               required maxlength="100">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label" for="vehicle_user_id">Owner :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <select name="Vehicle[user_id]" id="vehicle_user_id" class="w-100" required></select>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-12">
                <button type="submit" class="btn btn-primary">Proceed <i class="icon-arrow-right14 position-right"></i></button>
                <a href="{{ $returnUrl }}" class="btn btn-default">Cancel</a>
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
