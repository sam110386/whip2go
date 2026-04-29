@extends('admin.layouts.app')

@section('title', !empty($offer) ? 'Edit Vehicle Offer' : 'Add Vehicle Offer')

@section('content')
    @php
        $isEdit = !empty($offer->id ?? null);
        $headingLabel = $isEdit ? 'Edit' : 'Add';
    @endphp

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">{{ $headingLabel }}</span> - Vehicle Offer
                </h4>
                <div class="heading-elements">
                    <button type="submit" form="VehicleOfferForm" class="btn btn-primary heading-btn">
                        Save <i class="icon-database-insert position-right"></i>
                    </button>
                    <a href="{{ $basePath }}/index" class="btn btn-default heading-btn">
                        <i class="icon-arrow-left8 position-left"></i> Return
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        @includeif('partials.flash')

        <form method="POST" action="{{ $basePath }}/add{{ !empty($offer->id) ? '/' . base64_encode((string)$offer->id) : '' }}" id="VehicleOfferForm" name="VehicleOfferForm" class="form-horizontal">
            @csrf

            <div class="panel panel-flat">
                <div class="panel-heading">
                    <h5 class="panel-title">Vehicle Offer Details</h5>
                </div>

                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-lg-3 control-label">Dealer user id : <span class="text-danger">*</span></label>
                        <div class="col-lg-9">
                            <input type="number" class="form-control" name="VehicleOffer[user_id]" value="{{ $offer->user_id ?? '' }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label">Renter user id :</label>
                        <div class="col-lg-9">
                            <input type="number" class="form-control" name="VehicleOffer[renter_id]" value="{{ $offer->renter_id ?? '' }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label">Vehicle id : <span class="text-danger">*</span></label>
                        <div class="col-lg-9">
                            <input type="number" class="form-control" name="VehicleOffer[vehicle_id]" value="{{ $offer->vehicle_id ?? '' }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label">Offer price :</label>
                        <div class="col-lg-9">
                            <input type="number" step="0.01" class="form-control" name="VehicleOffer[offer_price]" value="{{ $offer->offer_price ?? 0 }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label">Finance type :</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control" name="VehicleOffer[finance_type]" value="{{ $offer->finance_type ?? '' }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label">Term :</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control" name="VehicleOffer[term]" value="{{ $offer->term ?? '' }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label">Down payment :</label>
                        <div class="col-lg-9">
                            <input type="number" step="0.01" class="form-control" name="VehicleOffer[down_payment]" value="{{ $offer->down_payment ?? 0 }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label">APR :</label>
                        <div class="col-lg-9">
                            <input type="number" step="0.01" class="form-control" name="VehicleOffer[apr]" value="{{ $offer->apr ?? 0 }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label">Monthly payment :</label>
                        <div class="col-lg-9">
                            <input type="number" step="0.01" class="form-control" name="VehicleOffer[monthly_payment]" value="{{ $offer->monthly_payment ?? 0 }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label">Status :</label>
                        <div class="col-lg-9">
                            <input type="number" class="form-control" name="VehicleOffer[status]" value="{{ $offer->status ?? 0 }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label">Note :</label>
                        <div class="col-lg-9">
                            <textarea class="form-control" name="VehicleOffer[note]" rows="3">{{ $offer->note ?? '' }}</textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label">&nbsp;</label>
                        <div class="col-lg-9">
                            <button type="submit" class="btn btn-primary">Save <i class="icon-database-insert position-right"></i></button>
                            <a href="{{ $basePath }}/index" class="btn btn-default left-margin">Return</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
