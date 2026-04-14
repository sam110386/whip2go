<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <form action="" method="POST" id="openLicenseScanRequestPopup" class="form-horizontal">
        @csrf
        @if(empty($pickupData) || (empty($pickupData['LicenseDetail']) && empty($pickupData['LICENSEDOC'])))
        <div class="alert alert-warning alert-styled-left">
            <span class="text-semibold">Licence Data: No data available</span>
        </div>
        @endif
        @if(!empty($pickupData) && !empty($pickupData['LicenseDetail']))
        <div class="col-lg-12">
            <span class="text-semibold">Licence Data:
                <pre class="content-group language-markup"><code class="language-markup">{!! print_r($pickupData['LicenseDetail'], true) !!}</code></pre>
            </span>
        </div>
        @endif
        @if(!empty($pickupData) && !empty($pickupData['LICENSEDOC']))
        <div class="col-lg-12">
            <span class="text-semibold">License Scan:</span><br/>
            @foreach($pickupData['LICENSEDOC'] as $doc)
            <img height="150px" width="150px" src="{{ config('app.url') }}files/reservation/{{ $doc }}">
            @endforeach
        </div>
        @endif
        @if($isAdmin !== 1)
        <fieldset class="col-lg-12">
            <legend class="text-semibold">Request for License Scan</legend>
            <div class="form-group">
                <label class="col-lg-3 control-label">Email :</label>
                <div class="col-lg-9">
                    <input type="email" name="Text[email]" class="form-control">
                </div>
            </div>
        </fieldset>
        @endif
        <input type="hidden" name="Text[booking]" value="{{ base64_encode($booking) }}">
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
    @if($isAdmin !== 1)
    <button type="button" class="btn btn-primary" onclick="saveLicenseScanPopupRequest()">Send</button>
    @endif
</div>
