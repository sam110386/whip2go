@extends('admin.layouts.app')

@section('title', 'Vehicle Offer Details')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Vehicle Offer</span> - #{{ $offer->id }}
                </h4>
                <div class="heading-elements">
                    <a href="{{ $basePath }}/index" class="btn btn-default heading-btn">
                        <i class="icon-arrow-left8 position-left"></i> Return
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        @includeif('partials.flash')

        <div class="panel panel-flat">
            <div class="panel-heading">
                <h5 class="panel-title">Offer Details</h5>
            </div>

            <div class="panel-body">
                <div class="form-horizontal">
                    <div class="form-group">
                        <label class="col-lg-3 control-label text-semibold">Vehicle :</label>
                        <div class="col-lg-9">
                            <p class="form-control-static">{{ $offer->vehicle_unique_id }} - {{ $offer->vehicle_name }}</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label text-semibold">Dealer :</label>
                        <div class="col-lg-9">
                            <p class="form-control-static">{{ trim(($offer->owner_first_name ?? '') . ' ' . ($offer->owner_last_name ?? '')) }}</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label text-semibold">Renter :</label>
                        <div class="col-lg-9">
                            <p class="form-control-static">{{ trim(($offer->renter_first_name ?? '') . ' ' . ($offer->renter_last_name ?? '')) }}</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label text-semibold">Offer price :</label>
                        <div class="col-lg-9">
                            <p class="form-control-static">{{ $offer->offer_price }}</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label text-semibold">Finance type :</label>
                        <div class="col-lg-9">
                            <p class="form-control-static">{{ $offer->finance_type }}</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label text-semibold">Term :</label>
                        <div class="col-lg-9">
                            <p class="form-control-static">{{ $offer->term }}</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label text-semibold">Status :</label>
                        <div class="col-lg-9">
                            <p class="form-control-static">{{ $offer->status }}</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label text-semibold">Note :</label>
                        <div class="col-lg-9">
                            <p class="form-control-static">{{ $offer->note }}</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label">&nbsp;</label>
                        <div class="col-lg-9">
                            <a href="{{ $basePath }}/index" class="btn btn-default">
                                <i class="icon-arrow-left8 position-left"></i> Return
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
