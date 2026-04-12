{{-- Cake `app/View/Smslogs/admin_details.ctp` (colorbox HTML) --}}
@if(empty($smslog))
    <p>No record found.</p>
@else
    <div class="rowe">
        <fieldset class="col-lg-10">
            <form class="form-horizontal">
                <div class="panel-body">
                    <div class="form-group">
                        <h3><div>Sms Details : </div></h3>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4"><strong>Type :</strong></label>
                        <div class="col-lg-6">{{ ((int)($smslog->type ?? 0) === 1) ? 'Sent' : 'Recieved' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4"><strong>Phone# :</strong></label>
                        <div class="col-lg-6">{{ e($smslog->renter_phone ?? '') }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4"><strong>Message :</strong></label>
                        <div class="col-lg-6">{!! nl2br(e($smslog->msg ?? '')) !!}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4"><strong>Timestamp :</strong></label>
                        <div class="col-lg-6">
                            @if(!empty($smslog->created))
                                {{ \Carbon\Carbon::parse($smslog->created)->format('m/d/Y h:i A') }}
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </fieldset>
    </div>
@endif
