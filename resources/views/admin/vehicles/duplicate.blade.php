@extends('layouts.admin')

@section('title', 'Duplicate Vehicle')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
    @php
        $base = $vehicleBasePath ?? '/admin/vehicles';
    @endphp
    <h1>Duplicate Vehicle</h1>

    @if (session('error'))
        <p style="color:#b00020;">{{ session('error') }}</p>
    @endif

    <form method="POST" action="{{ $base }}/duplicate/{{ base64_encode((string)($vehicleid ?? 0)) }}" id="vehicle-duplicate-form">
        @csrf
        <div style="margin:8px 0;">
            <label>VIN Number* <input type="text" name="Vehicle[vin_no]" style="text-transform:uppercase;" required maxlength="100"></label>
        </div>
        <div style="margin:8px 0; max-width: 420px;">
            <label for="vehicle_user_id">Owner*</label>
            <select name="Vehicle[user_id]" id="vehicle_user_id" style="width:100%;" required></select>
        </div>
        <button type="submit">Proceed</button>
        <a href="{{ $returnListUrl ?? ($base . '/index') }}" style="margin-left:10px;">Cancel</a>
    </form>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
