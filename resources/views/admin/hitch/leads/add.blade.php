@extends('admin.layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
@endpush

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold"></span> {{ $listTitle }}
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="row">
            <form action="/admin/hitch/leads/add" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
                @csrf
                <div class="panel-body">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Dealer :<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="text" name="HitchLead[dealer_id]" id="HitchLeadDealerId" class="required"
                                    style="width:100%" value="{{ $lead->dealer_id ?? '' }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Phone :<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="text" name="HitchLead[phone]" maxlength="12"
                                    class="form-control phone required" value="{{ $lead->phone ?? '' }}" {{ !empty($lead->id ?? null) ? 'readonly' : '' }}>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">First Name :<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="text" name="HitchLead[first_name]" class="form-control required"
                                    value="{{ $lead->first_name ?? '' }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Last Name :<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="text" name="HitchLead[last_name]" class="form-control required"
                                    value="{{ $lead->last_name ?? '' }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Email :</label>
                            <div class="col-lg-9">
                                <input type="text" name="HitchLead[email]" class="form-control email"
                                    value="{{ $lead->email ?? '' }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">On Payroll :</label>
                            <div class="col-lg-9">
                                <select name="HitchLead[payroll]" class="form-control">
                                    <option value="0" {{ ($lead->payroll ?? 0) == 0 ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ ($lead->payroll ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="col-lg-2 control-label">&nbsp;</label>
                            <div class="col-lg-6">
                                @if (empty($lead->id ?? null))
                                    <button type="submit" class="btn">Save</button>
                                @else
                                    <button type="submit" class="btn">Update</button>
                                @endif
                                <button type="button" class="btn left-margin btn-cancel"
                                    onclick="goBack('/admin/hitch/leads/index')">Return</button>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="HitchLead[id]" value="{{ $lead->id ?? '' }}">
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
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Dealer",
                minimumInputLength: 1,
                ajax: {
                    url: "{{ config('app.url') }}/admin/bookings/customerautocomplete",
                    dataType: "json", type: "GET",
                    data: function (params) { return { term: params, "is_dealer": true } },
                    processResults: function (data) {
                        return { results: $.map(data, function (item) { return { tag: item.tag, id: item.id } }) };
                    }
                },
                initSelection: function (element, callback) {
                    var dealer_id = "{{ $lead->dealer_id ?? '' }}";
                    if (dealer_id.length > 0) {
                        jQuery.ajax({
                            url: "{{ config('app.url') }}/admin/bookings/customerautocomplete",
                            dataType: "json", type: "GET", data: { "id": dealer_id }
                        }).done(function (data) { callback(data[0]); });
                    }
                }
            });
        });
    </script>
@endpush