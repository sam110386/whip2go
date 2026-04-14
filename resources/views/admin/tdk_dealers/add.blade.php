@extends('admin.layouts.app')

@section('title', $listTitle ?? 'TDK Dealer')

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
    @php
        $dealerId = old('TdkDealer.id', $dealer->id ?? null);
        $actionUrl = $basePath . '/add' . ($dealerId ? '/' . base64_encode((string) $dealerId) : '');
        $initialUserId = old('TdkDealer.user_id', $dealer->user_id ?? null);
    @endphp
    <div class="row">
        @if(session('success'))
            <div class="col-md-12"><div class="alert alert-success">{{ session('success') }}</div></div>
        @endif
        @if(session('error'))
            <div class="col-md-12"><div class="alert alert-danger">{{ session('error') }}</div></div>
        @endif
    </div>
    <form method="POST" action="{{ $actionUrl }}" name="TdkDealer" id="TdkDealer" class="form-horizontal">
        @csrf
        <div class="row">
            <div class="panel panel-flat">
                <div class="panel-heading">
                    <h5 class="panel-title">{{ $listTitle ?? 'TDK Dealer' }}</h5>
                </div>
                <div class="panel-body">
                    <div class="col-sm-12">
                        <legend class="text-semibold">Enter All Information</legend>

                        <div class="form-group">
                            <label class="col-lg-2 control-label">Dealer :</label>
                            <div class="col-lg-4">
                                <select id="tdk_dealer_user_id" name="TdkDealer[user_id]" class="form-control" style="width:100%;" data-placeholder="Select Dealer">
                                    @if($initialUserId)
                                        <option value="{{ $initialUserId }}" selected>{{ $userLabel ?: 'Selected dealer' }}</option>
                                    @endif
                                </select>
                                @error('TdkDealer.user_id')
                                    <span class="help-block text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Metro City :</label>
                            <div class="col-lg-4">
                                <input type="text" name="TdkDealer[metro_city]" class="required form-control" placeholder="Metro Town"
                                       value="{{ old('TdkDealer.metro_city', $dealer->metro_city ?? '') }}">
                                @error('TdkDealer.metro_city')
                                    <span class="help-block text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Plate State :</label>
                            <div class="col-lg-4">
                                <input type="text" name="TdkDealer[metro_state]" maxlength="3" class="required form-control" placeholder="Metro State"
                                       value="{{ old('TdkDealer.metro_state', $dealer->metro_state ?? '') }}">
                                @error('TdkDealer.metro_state')
                                    <span class="help-block text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Status :</label>
                            <div class="col-lg-4">
                                @php $st = (int) old('TdkDealer.status', $dealer->status ?? 1); @endphp
                                <select name="TdkDealer[status]" class="form-control">
                                    <option value="1" @selected($st === 1)>Active</option>
                                    <option value="0" @selected($st === 0)>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">&nbsp;</label>
                            <div class="col-lg-4">
                                <button type="submit" class="focus_text btn no-margin" style="float:right">Save</button>
                                <button type="button" class="btn left-margin btn-cancel" onclick="window.location.href='{{ $basePath }}/index'">Return</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="TdkDealer[id]" value="{{ old('TdkDealer.id', $dealer->id ?? '') }}">
    </form>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        (function () {
            var dealerId = @json($initialUserId !== null && $initialUserId !== '' ? (int) $initialUserId : null);
            var $sel = jQuery('#tdk_dealer_user_id');
            $sel.select2({
                placeholder: 'Select Dealer',
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
                jQuery.getJSON('/admin/bookings/customerautocomplete', { id: dealerId })
                    .done(function (data) {
                        if (data && data.length) {
                            var item = data[0];
                            var opt = new Option(item.tag, item.id, true, true);
                            $sel.append(opt).trigger('change');
                        }
                    });
            }
            jQuery('#TdkDealer').validate();
        })();
    </script>
@endpush
