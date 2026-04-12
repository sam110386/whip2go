@extends('layouts.main')

@section('title', $title_for_layout ?? 'My Dashboard')
@section('header_title', $title_for_layout ?? 'My Dashboard')

@section('content')
{{-- Port of Cake `app/View/Dashboard/index.ctp` --}}
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-flat">
            <div class="panel-heading">
                <h6 class="panel-title">Sales statistics</h6>
                <div class="heading-elements">
                    <form class="heading-form" action="#">
                        <div class="form-group">
                            <select id="saleStaticsDate" class="change-date select-sm" onchange="saleStaticsDateChange(this)">
                                <option value="month" selected="selected">Current Month</option>
                                <option value="halfyear">Last 6 Month</option>
                                <option value="year">Current Year</option>
                                <option value="lifetime">Lifetime</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            <div class="container-fluid" id="salestatics"></div>
            <div class="content-group-sm" id="app_sales"></div>
            <div id="monthly-sales-stats"></div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-4" id="vehiclesummary"></div>
    <div class="col-lg-8" id="bookingsummary"></div>
</div>
<div class="row">
    <div class="col-lg-8" id="vehiclereport"></div>
    <div class="col-lg-4">
        <div class="panel panel-flat">
            <div class="text-center bg-theme">
                <div class="panel-heading">
                    <h6 class="panel-title">Recent messages</h6>
                    <div class="heading-elements">
                        <a href="/smslogs/index" class="text-white">More <i class="icon-arrow-right16 position-right"></i></a>
                    </div>
                </div>
            </div>
            <div id="messages-stats"></div>
            <ul class="nav nav-lg nav-tabs nav-justified no-margin no-border-radius bg-indigo-400 border-top border-top-indigo-300">
                <li class="active">
                    <a href="#inbound" class="text-size-small text-uppercase" data-toggle="tab">Inbound</a>
                </li>
                <li>
                    <a href="#outbound" class="text-size-small text-uppercase" data-toggle="tab">Outbound</a>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane active fade in has-padding" id="inbound">
                    <ul class="media-list">
                        @foreach($inbounds as $inbound)
                            <li class="media">
                                <div class="media-left">{{ e($inbound->increment_id ?? '') }}</div>
                                <div class="media-body">
                                    <a href="#">
                                        {{ e($inbound->renter_phone ?? '') }}
                                        <span class="media-annotation pull-right">
                                            @if(!empty($inbound->created))
                                                {{ \Carbon\Carbon::parse($inbound->created)->format('l H:i') }}
                                            @endif
                                        </span>
                                    </a>
                                    <span class="display-block text-muted">{{ e(\Illuminate\Support\Str::limit((string)($inbound->msg ?? ''), 30, '…')) }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="tab-pane fade has-padding" id="outbound">
                    <ul class="media-list">
                        @foreach($outbounds as $outbound)
                            <li class="media">
                                <div class="media-left">{{ e($outbound->increment_id ?? '') }}</div>
                                <div class="media-body">
                                    <a href="#">
                                        {{ e($outbound->renter_phone ?? '') }}
                                        <span class="media-annotation pull-right">
                                            @if(!empty($outbound->created))
                                                {{ \Carbon\Carbon::parse($outbound->created)->format('l H:i') }}
                                            @endif
                                        </span>
                                    </a>
                                    <span class="display-block text-muted">{{ e(\Illuminate\Support\Str::limit((string)($outbound->msg ?? ''), 30, '…')) }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ legacy_asset('js/dashboard.js') }}"></script>
@endpush
