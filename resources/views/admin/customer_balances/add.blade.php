@extends('admin.layouts.app')

@section('title', $listTitle)

@push('scripts')
<script type="text/javascript" src="{{ legacy_asset('js/assets/js/plugins/forms/selects/select2.min.js') }}"></script>
<script type="text/javascript">
    function format(item) {
        return item.tag;
    }
    jQuery(document).ready(function() {
        var $select = jQuery("#CsUserBalanceUserId");
        
        $select.select2({
            placeholder: "Select Customer",
            minimumInputLength: 1,
            ajax: {
                url: SITE_URL + "admin/bookings/customerautocomplete",
                dataType: "json",
                type: "GET",
                delay: 250,
                data: function (params) {
                    return {term: params.term}
                },
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {text: item.tag, id: item.id}
                        })
                    };
                }
            }
        });

        // Initialize display if ID exists
        var renter_id = "{{ $balance?->user_id ?? request('user_id', '') }}";
        if (renter_id.length > 0) {
            jQuery.ajax({
                url: SITE_URL + "admin/bookings/customerautocomplete",
                dataType: "json",
                type: "GET",
                data: {"id": renter_id}
            }).done(function (data) {
                if (data && data.length > 0) {
                    var item = data[0];
                    var option = new Option(item.tag, item.id, true, true);
                    $select.append(option).trigger('change.select2');
                }
            });
        }

        $select.on('change', function(e) {
            var userId = $(this).val();
            if (userId && userId != renter_id) {
                @if (!$balance)
                    window.location.href = SITE_URL + "admin/customer_balances/add?user_id=" + userId;
                @endif
            }
        });

        $("#frmadmin").validate();

        $("#CsUserBalanceChargetype").change(function () {
            if ($(this).val() === 'installment') {
                $(".installment").show();
                $(".subscription").show();
            } else if ($(this).val() === 'subscription') {
                $(".installment").hide();
                $(".subscription").hide();
            } else {
                $(".installment").hide();
                $(".subscription").show();
            }
        });
    });   
</script>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold"> </span> {{ $listTitle }}</h4>
        </div>
    </div>
</div>

<div class="row">
    @include('partials.flash')
</div>

<div class="panel">
    <div class="panel-body">
        <div class="row">
            <form method="POST"
                  action="{{ $balance ? url('admin/customer_balances/add', base64_encode((string)$balance->id)) : url('admin/customer_balances/add') }}"
                  class="form-horizontal" id="frmadmin" name="frmadmin">
                @csrf
                @if ($balance)
                    <input type="hidden" name="CsUserBalance[id]" value="{{ $balance->id }}">
                @endif

                <div class="col-lg-12">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Driver :</label>
                            <div class="col-lg-8">
                                <select name="CsUserBalance[user_id]" id="CsUserBalanceUserId" class="form-control required" style="width:100%">
                                    @if($balance || request('user_id'))
                                        <option value="{{ $balance?->user_id ?? request('user_id') }}" selected="selected">
                                            Loading...
                                        </option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Status :</label>
                            <div class="col-lg-8">
                                <select name="CsUserBalance[status]" class="form-control">
                                    <option value="1" @selected((int)old('CsUserBalance.status', $balance?->status ?? 1) === 1)>Active</option>
                                    <option value="0" @selected((int)old('CsUserBalance.status', $balance?->status ?? 1) === 0)>Inactive</option>
                                    <option value="2" @selected((int)old('CsUserBalance.status', $balance?->status ?? 1) === 2)>Completed</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="col-lg-4 control-label text-bold">Charge To Driver Amount :</label>
                            <div class="col-lg-8">{{ $balance ? $balance->credit : 0 }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label text-bold">Debit :</label>
                            <div class="col-lg-8">{{ $balance ? $balance->debit : 0 }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label text-bold">Balance :</label>
                            <div class="col-lg-8">{{ $balance ? $balance->balance : 0 }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="col-lg-6">
                        <legend><center>Update Balance</center></legend>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Credit/Debit :</label>
                            <div class="col-lg-8">
                                <select name="CsUserBalance[creditdebit]" class="form-control">
                                    <option value="credit">Charge To Driver</option>
                                    {{-- <option value="debit">Debit</option> --}}
                                </select>
                                <em>Credit : Charge to Driver, Debit : Give Refund to Customer</em>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Type :</label>
                            <div class="col-lg-8">
                                <select name="CsUserBalance[type]" class="form-control">
                                    @foreach ($balanceTypes as $k => $label)
                                        <option value="{{ $k }}" @selected((string)old('CsUserBalance.type', $balance?->type ?? '') === (string)$k)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Amount :</label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="CsUserBalance[balance]"
                                       value="{{ old('CsUserBalance.balance') }}"
                                       class="number form-control required">
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <legend><center>Balance Capture Setting</center></legend>
                        <div class="form-group">
                            <label class="col-lg-4 control-label text-right">Capture As :</label>
                            <div class="col-lg-8">
                                <select name="CsUserBalance[chargetype]" id="CsUserBalanceChargetype" class="form-control">
                                    <option value="lumpsum"      @selected(old('CsUserBalance.chargetype', $balance?->chargetype ?? 'lumpsum') === 'lumpsum')>Lumpsum</option>
                                    <option value="installment"  @selected(old('CsUserBalance.chargetype', $balance?->chargetype ?? '') === 'installment')>Installment</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group installment" @if(old('CsUserBalance.chargetype', $balance?->chargetype ?? '') !== 'installment') style="display:none;" @endif>
                            <label class="col-lg-4 control-label text-right">Installment Type :</label>
                            <div class="col-lg-8">
                                <select name="CsUserBalance[installment_type]" class="form-control">
                                    <option value="daily"   @selected(old('CsUserBalance.installment_type', $balance?->installment_type ?? 'daily') === 'daily')>Daily</option>
                                    <option value="weekly"  @selected(old('CsUserBalance.installment_type', $balance?->installment_type ?? '') === 'weekly')>Weekly</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label text-right">Week Day :</label>
                            <div class="col-lg-8">
                                <select name="CsUserBalance[installment_day]" class="form-control">
                                    @foreach ($weekdays as $k => $label)
                                        <option value="{{ $k }}" @selected(old('CsUserBalance.installment_day', $balance?->installment_day ?? 'sun') === $k)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group installment" @if(old('CsUserBalance.chargetype', $balance?->chargetype ?? '') !== 'installment') style="display:none;" @endif>
                            <label class="col-lg-4 control-label text-right">Installment :</label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="CsUserBalance[installment]"
                                       value="{{ old('CsUserBalance.installment', $balance?->installment ?? '0') }}"
                                       class="digit form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">Note :</label>
                        <div class="col-lg-8">
                            <textarea name="CsUserBalance[note]" rows="3" class="form-control"
                                      maxlength="255">{{ old('CsUserBalance.note', $balance?->note ?? '') }}</textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-3">
                            <button type="submit" class="btn left-margin btn-warning btn-block">Save</button>
                        </div>
                        <div class="col-lg-3">
                            <a href="{{ url('admin/customer_balances/index') }}"
                               class="btn bg-pink btn-block">Return</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
