@extends('layouts.admin')

@section('content')
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery("#frmadmin").validate();
    });
</script>

<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">{{ $listTitle }}</span> - Dealer</h4>
        </div>
    </div>
</div>

<div class="row">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
</div>

<div class="panel">
    <div class="row">
        <fieldset class="col-lg-12">
            <form action="{{ url('admin/savvy/dealers/add') }}" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
                @csrf
                <div class="panel-body">

                    <div class="form-group">
                        <label class="col-lg-2 control-label">Dealer :<span class="text-danger">*</span></label>
                        <div class="col-lg-9">
                            @if(empty($dealer->id ?? null))
                                <input type="text" name="SavvyDealer[user_id]" id="SavvyDealerUserId" class="required" placeholder="Dealer" style="width:100%;" value="{{ old('SavvyDealer.user_id') }}">
                            @else
                                {{ $dealer->first_name ?? '' }} {{ $dealer->last_name ?? '' }}
                                <input type="hidden" name="SavvyDealer[user_id]" value="{{ $dealer->user_id ?? '' }}">
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label">Resource Url :</label>
                        <div class="col-lg-9">
                            <input type="text" name="SavvyDealer[search_url]" class="url form-control required" value="{{ $dealer->search_url ?? old('SavvyDealer.search_url') }}">
                        </div>
                    </div>

                    <legend class="text-size-large text-bold">Custom Filter :</legend>

                    <div class="form-group">
                        <label class="col-lg-2 control-label"></label>
                        <div class="col-lg-9">
                            <label class="col-lg-2 control-label">Selling Price :</label>
                            <label class="col-lg-1 control-label">From</label>
                            <div class="col-lg-2">
                                <input type="text" name="SavvyDealer[filters][sellingprice][from]" class="form-control" value="{{ $dealer->filters_decoded['sellingprice']['from'] ?? old('SavvyDealer.filters.sellingprice.from') }}">
                            </div>
                            <label class="col-lg-1 control-label">To</label>
                            <div class="col-lg-2">
                                <input type="text" name="SavvyDealer[filters][sellingprice][to]" class="form-control" value="{{ $dealer->filters_decoded['sellingprice']['to'] ?? old('SavvyDealer.filters.sellingprice.to') }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label"></label>
                        <div class="col-lg-9">
                            <label class="col-lg-2 control-label">Make :</label>
                            <div class="col-lg-6">
                                <input type="text" name="SavvyDealer[filters][make]" class="form-control" value="{{ $dealer->filters_decoded['make'] ?? old('SavvyDealer.filters.make') }}">
                                <span class="help-block">Please enter comma's (",") separated values if more than one</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label"></label>
                        <div class="col-lg-9">
                            <label class="col-lg-2 control-label">Model :</label>
                            <div class="col-lg-6">
                                <input type="text" name="SavvyDealer[filters][model]" class="form-control" value="{{ $dealer->filters_decoded['model'] ?? old('SavvyDealer.filters.model') }}">
                                <span class="help-block">Please enter comma's (",") separated values if more than one</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label"></label>
                        <div class="col-lg-9">
                            <label class="col-lg-2 control-label">Model Year Range :</label>
                            <div class="col-lg-6">
                                <input type="text" name="SavvyDealer[filters][year]" class="form-control" placeholder="YYYY-YYYY" value="{{ $dealer->filters_decoded['year'] ?? old('SavvyDealer.filters.year') }}">
                                <span class="help-block">YYYY-YYYY</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label text-bold">Only X days Older :</label>
                        <div class="col-lg-9">
                            <label class="col-lg-3 control-label">if Odometer is more than</label>
                            <div class="col-lg-1">
                                <input type="text" name="SavvyDealer[filters][odometer]" class="form-control" placeholder="100" value="{{ $dealer->filters_decoded['odometer'] ?? old('SavvyDealer.filters.odometer') }}">
                            </div>
                            <label class="col-lg-2 control-label">days allow older</label>
                            <div class="col-lg-2">
                                <input type="text" name="SavvyDealer[filters][older_days]" class="form-control" placeholder="XX" value="{{ $dealer->filters_decoded['older_days'] ?? old('SavvyDealer.filters.older_days') }}">
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="col-lg-2 control-label">&nbsp;</label>
                            <div class="col-lg-6">
                                @if(empty($dealer->id ?? null))
                                    <button type="submit" class="btn">Save</button>
                                @else
                                    <button type="submit" class="btn">Update</button>
                                @endif
                                <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/admin/savvy/dealers/index')">Return</button>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="SavvyDealer[id]" value="{{ $dealer->id ?? '' }}">
            </form>
        </fieldset>
    </div>
</div>

@if(empty($dealer->id ?? null))
<link rel="stylesheet" href="{{ asset('css/select2.css') }}">
<script src="{{ asset('js/select2.js') }}"></script>
<script type="text/javascript">
    function format(item) {
        return item.tag;
    }
    jQuery(document).ready(function () {
        jQuery("#SavvyDealerUserId").select2({
            data: {results: {}, text: 'tag'},
            formatSelection: format,
            formatResult: format,
            placeholder: "Select Dealer ",
            minimumInputLength: 1,
            ajax: {
                url: "{{ config('app.url') }}admin/bookings/customerautocomplete",
                dataType: "json",
                type: "GET",
                data: function (params) {
                    return {term: params, is_dealer: true}
                },
                processResults: function (data) {
                    return {
                        results: jQuery.map(data, function (item) {
                            return {tag: item.tag, id: item.id}
                        })
                    };
                }
            }
        });
    });
</script>
@endif
@endsection
