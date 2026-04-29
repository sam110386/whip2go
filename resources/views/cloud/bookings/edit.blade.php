@extends('layouts.main')

@section('title', 'Edit Booking')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Edit Booking</span> #{{ $order->increment_id ?? $order->id }}
                </h4>
            </div>
            <div class="heading-elements">
                <div class="heading-btn-group">
                    <button type="submit" form="frmadmin" class="btn btn-primary">Save</button>
                    <a href="/cloud/linked_bookings/index" class="btn btn-default">Return</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <form method="POST" action="/cloud/linked_bookings/editsave" id="frmadmin" name="frmadmin" class="form-horizontal">
        @csrf
        <input type="hidden" name="CsOrder[id]" value="{{ $order->id }}">

        <div class="panel panel-flat">
            <div class="panel-heading">
                <h5 class="panel-title">Booking Details</h5>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">Start datetime :</label>
                    <div class="col-lg-9">
                        <input type="text" name="CsOrder[start_datetime]" class="form-control"
                               value="{{ $order->start_datetime ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">End datetime :</label>
                    <div class="col-lg-9">
                        <input type="text" name="CsOrder[end_datetime]" class="form-control"
                               value="{{ $order->end_datetime ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Rent :</label>
                    <div class="col-lg-9">
                        <input type="number" step="0.01" name="CsOrder[rent]" class="form-control"
                               value="{{ $order->rent ?? 0 }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Tax :</label>
                    <div class="col-lg-9">
                        <input type="number" step="0.01" name="CsOrder[tax]" class="form-control"
                               value="{{ $order->tax ?? 0 }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">DIA fee :</label>
                    <div class="col-lg-9">
                        <input type="number" step="0.01" name="CsOrder[dia_fee]" class="form-control"
                               value="{{ $order->dia_fee ?? 0 }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Status :</label>
                    <div class="col-lg-9">
                        <input type="number" name="CsOrder[status]" class="form-control"
                               value="{{ $order->status ?? 0 }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Cancel note :</label>
                    <div class="col-lg-9">
                        <textarea name="CsOrder[cancel_note]" rows="3" class="form-control">{{ $order->cancel_note ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-12">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="/cloud/linked_bookings/index" class="btn btn-default">Return</a>
            </div>
        </div>
    </form>
@endsection
