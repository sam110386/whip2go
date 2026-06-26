@extends('admin.layouts.app')

@php
    $dealer ??= null;
    $listTitle ??= 'Add';
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">{{ $listTitle }}</span> - Dealer
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="row">
            <fieldset class="col-lg-12">
                <form action="{{ url('admin/savvy_dealers/add/' . base64_encode(data_get($dealer, 'id', ''))) }}"
                    method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
                    @csrf
                    <div class="panel-body">

                        <div class="form-group">
                            <label class="col-lg-2 control-label">
                                Dealer :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-9">
                                @if(empty(data_get($dealer, 'id', '')))
                                    <input type="text" name="SavvyDealer[user_id]" id="SavvyDealerUserId" class="required"
                                        placeholder="Dealer" style="width:100%;" value="{{ old('SavvyDealer.user_id') }}">
                                @else
                                    {{ data_get($dealer, 'user.first_name', '') }} {{ data_get($dealer, 'user.last_name', '')}}
                                    <input type="hidden" name="SavvyDealer[user_id]"
                                        value="{{ data_get($dealer, 'user_id', '') }}">
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-2 control-label">Resource Url :</label>
                            <div class="col-lg-9">
                                <input type="text" name="SavvyDealer[search_url]" class="url form-control required"
                                    value="{{ old('SavvyDealer.search_url', data_get($dealer, 'search_url', '')) }}">
                            </div>
                        </div>

                        <legend class="text-size-large text-bold">Custom Filter :</legend>

                        <div class="form-group">
                            <label class="col-lg-2 control-label"></label>
                            <div class="col-lg-9">
                                <label class="col-lg-2 control-label">Selling Price :</label>
                                <label class="col-lg-1 control-label">From</label>
                                <div class="col-lg-2">
                                    <input type="text" name="SavvyDealer[filters][sellingprice][from]" class="form-control"
                                        value="{{ old('SavvyDealer.filters.sellingprice.from', data_get($dealer, 'filters.sellingprice.from', '')) }}">
                                </div>
                                <label class="col-lg-1 control-label">To</label>
                                <div class="col-lg-2">
                                    <input type="text" name="SavvyDealer[filters][sellingprice][to]" class="form-control"
                                        value="{{ old('SavvyDealer.filters.sellingprice.to', data_get($dealer, 'filters.sellingprice.to', '')) }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-2 control-label"></label>
                            <div class="col-lg-9">
                                <label class="col-lg-2 control-label">Make :</label>
                                <div class="col-lg-6">
                                    <input type="text" name="SavvyDealer[filters][make]" class="form-control"
                                        value="{{ old('SavvyDealer.filters.make', data_get($dealer, 'filters.make', '')) }}">
                                    <span class="help-block">
                                        Please enter comma's (",") separated values if more than one
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-2 control-label"></label>
                            <div class="col-lg-9">
                                <label class="col-lg-2 control-label">Model :</label>
                                <div class="col-lg-6">
                                    <input type="text" name="SavvyDealer[filters][model]" class="form-control"
                                        value="{{ old('SavvyDealer.filters.model', data_get($dealer, 'filters.model', '')) }}">
                                    <span class="help-block">Please enter comma's (",") separated values if more than
                                        one</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-2 control-label"></label>
                            <div class="col-lg-9">
                                <label class="col-lg-2 control-label">Model Year Range :</label>
                                <div class="col-lg-6">
                                    <input type="text" name="SavvyDealer[filters][year]" class="form-control"
                                        placeholder="YYYY-YYYY"
                                        value="{{ old('SavvyDealer.filters.year', data_get($dealer, 'filters.year', '')) }}">
                                    <span class="help-block">YYYY-YYYY</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-2 control-label text-bold">Only X days Older :</label>
                            <div class="col-lg-9">
                                <label class="col-lg-3 control-label">if Odometer is more than</label>
                                <div class="col-lg-1">
                                    <input type="text" name="SavvyDealer[filters][odometer]" class="form-control"
                                        placeholder="100"
                                        value="{{ old('SavvyDealer.filters.odometer', data_get($dealer, 'filters.odometer', '')) }}">
                                </div>
                                <label class="col-lg-2 control-label">days allow older</label>
                                <div class="col-lg-2">
                                    <input type="text" name="SavvyDealer[filters][older_days]" class="form-control"
                                        placeholder="XX"
                                        value="{{ old('SavvyDealer.filters.older_days', data_get($dealer, 'filters.older_days', '')) }}">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="form-group">
                                <label class="col-lg-2 control-label">&nbsp;</label>
                                <div class="col-lg-6">
                                    @if(empty(data_get($dealer, 'id', '') ?? null))
                                        <button type="submit" class="btn">Save</button>
                                    @else
                                        <button type="submit" class="btn">Update</button>
                                    @endif
                                    <button type="button" class="btn left-margin btn-cancel"
                                        onclick="goBack('/admin/savvy_dealers/index')">
                                        Return
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="SavvyDealer[id]" value="{{ base64_encode(data_get($dealer, 'id', '')) }}">
                </form>
            </fieldset>
        </div>
    </div>
@endsection

@push('scripts')

    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("#frmadmin").validate();
        });
    </script>

    @if(empty(data_get($dealer, 'id', '')))
        <script src="{{ legacy_asset('js/select2.js') }}"></script>

        <script type="text/javascript">
            function format(item) {
                return item.tag;
            }

            jQuery(document).ready(function () {
                jQuery("#SavvyDealerUserId").select2({
                    data: { results: {}, text: 'tag' },
                    formatSelection: format,
                    formatResult: format,
                    placeholder: "Select Dealer ",
                    minimumInputLength: 1,
                    ajax: {
                        url: "{{ url('admin/bookings/customerautocomplete') }}",
                        dataType: "json",
                        type: "GET",
                        data: function (params) {
                            return {
                                term: params,
                                is_dealer: true
                            }
                        },
                        processResults: function (data) {
                            return {
                                results: jQuery.map(data, function (item) {
                                    return {
                                        tag: item.tag,
                                        id: item.id
                                    }
                                })
                            };
                        }
                    }
                });
            });

        </script>
    @endif

@endpush