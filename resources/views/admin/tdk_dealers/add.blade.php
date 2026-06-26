@extends('admin.layouts.app')

@php
    $title ??= 'Add TDK Dealer';
    $dealer ??= [];
@endphp

@section('title', $title)

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
@endpush

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">{{ $title }}</span>
                </h4>
                <div class="heading-elements">
                    <button type="submit" form="TdkDealer" class="btn btn-primary heading-btn">
                        Save <i class="icon-database-insert position-right"></i>
                    </button>
                    <a href="{{ url('admin/tdk_dealers/index') }}" class="btn btn-default heading-btn">
                        <i class="icon-arrow-left8 position-left"></i> Return
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <form method="POST" action="{{ url('admin/tdk_dealers/add/' . base64_encode(data_get($dealer, 'id', ''))) }}"
        name="TdkDealer" id="TdkDealer" class="form-horizontal">
        @csrf

        <div class="panel panel-flat">
            <div class="panel-heading">
                <h5 class="panel-title">{{ $title }}</h5>
            </div>

            <div class="panel-body">
                <legend class="text-semibold">Enter All Information</legend>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Dealer :</label>
                    <div class="col-lg-9">
                        <input type="text" id="tdk_dealer_user_id" name="TdkDealer[user_id]" style="width:100%;"
                            value="{{ old('TdkDealer.user_id', data_get($dealer, 'user_id', ''))}}"
                            data-placeholder="Select Dealer">
                        @error('TdkDealer.user_id')
                            <span class="help-block text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Metro City :</label>
                    <div class="col-lg-9">
                        <input type="text" name="TdkDealer[metro_city]" class="required form-control"
                            placeholder="Metro Town"
                            value="{{ old('TdkDealer.metro_city', data_get($dealer, 'metro_city', '')) }}">
                        @error('TdkDealer.metro_city')
                            <span class="help-block text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Plate State :</label>
                    <div class="col-lg-9">
                        <input type="text" name="TdkDealer[metro_state]" maxlength="3" class="required form-control"
                            placeholder="Metro State"
                            value="{{ old('TdkDealer.metro_state', data_get($dealer, 'metro_state', '')) }}">
                        @error('TdkDealer.metro_state')
                            <span class="help-block text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Status :</label>
                    <div class="col-lg-9">
                        @php $st = (int) old('TdkDealer.status', data_get($dealer, 'status', 1)); @endphp
                        <select name="TdkDealer[status]" class="form-control">
                            <option value="1" @selected($st === 1)>Active</option>
                            <option value="0" @selected($st === 0)>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">&nbsp;</label>
                    <div class="col-lg-9">
                        <button type="submit" class="btn btn-primary">
                            Save <i class="icon-database-insert position-right"></i>
                        </button>
                        <button type="button" class="btn btn-default left-margin btn-cancel"
                            onclick="window.location.href='{{ url('admin/tdk_dealers/index') }}'">
                            Return
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="TdkDealer[id]" value="{{ old('TdkDealer.id', data_get($dealer, 'id', '')) }}">
    </form>

@endsection

@push('scripts')

    <script src="{{ legacy_asset('js/select2.js') }}"></script>

    <script>
        (function () {
            var dealerId = @js(old('TdkDealer.user_id', data_get($dealer, 'user_id', '')));
            var $sel = jQuery('#tdk_dealer_user_id');

            $sel.select2({
                placeholder: 'Select Dealer',
                allowClear: true,
                minimumInputLength: 1,
                ajax: {
                    url: '/admin/bookings/customerautocomplete',
                    dataType: 'json',
                    quietMillis: 250,
                    data: function (term) {
                        return { term: term, is_dealer: true };
                    },
                    results: function (data) {
                        return {
                            results: (data || []).map(function (item) {
                                return { id: item.id, text: item.tag };
                            })
                        };
                    }
                },
                initSelection: function (element, callback) {
                    var id = jQuery(element).val();
                    if (id !== "") {
                        jQuery.getJSON('/admin/bookings/customerautocomplete', { id: id })
                            .done(function (data) {
                                if (data && data.length) {
                                    callback({ id: data[0].id, text: data[0].tag });
                                }
                            });
                    }
                }
            });

            jQuery('#TdkDealer').validate();

        })();

    </script>
@endpush