@extends('admin.layouts.app')

@section('title', $title ?? 'Review Waiting Orders')

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Review</span> Returns
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div style="width:100%; overflow: visible;" id="listing">
                @include('admin.booking_reviews.elements.nonreview')
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/assets/js/plugins/notifications/sweet_alert.min.js') }}"></script>
    <script src="{{ legacy_asset('js/admin_booking.js') }}"></script>
    <script src="{{ legacy_asset('js/ordernote/order_note.js') }}"></script>
@endpush