@extends('layouts.main')

@section('title', 'Linked Rental Orders')

@push('scripts')
<script src="{{ legacy_asset('assets/js/plugins/notifications/sweet_alert.min.js') }}"></script>
<script src="{{ legacy_asset('js/cloud_booking.js') }}"></script>
<script>
    jQuery(document).ready(function () {
        setInterval(function () {
            $.post('/cloud/linked_bookings/index/1', function (data) {
                $('#update_log').html(data);
            });
        }, 90000);
    });
</script>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Rental </span>- Active Orders</h4>
        </div>
        <div class="heading-elements"></div>
    </div>
</div>

<div class="row">
    @includeif('partials.flash')
</div>

<div class="panel">
    <div class="panel-body" id="listing">
        <div id="update_log" style="width:100%; overflow:visible;">
            @include('cloud.linked_bookings.booking_table', ['trips' => $trips])
        </div>
    </div>
</div>

<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content"></div>
    </div>
</div>
@endsection
