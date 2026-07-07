<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    @if($csorder)
        @php
            $orderId = data_get($csorder, 'id', '');
            $rawEndTiming = data_get($csorder, 'end_timing', '');
            $timezone = data_get($csorder, 'timezone', 'UTC');
            $formattedDate = '';

            if (!empty($rawEndTiming)) {
                try {
                    $date = is_numeric($rawEndTiming) ? new \DateTime("@{$rawEndTiming}") : new \DateTime($rawEndTiming);
                    $date->setTimezone(new \DateTimeZone($timezone));
                    $formattedDate = $date->format('m/d/Y h:i A');
                } catch (\Exception $e) {
                    $formattedDate = '';
                }
            }
        @endphp

        <form id="endTimeChangeForm" action="#" method="POST" class="form-horizontal">
            @csrf
            <fieldset>
                <legend class="text-semibold">Adjust Actual End Time </legend>

                <div class="form-group">
                    <label class="col-lg-4 control-label">
                        Date Time :
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="CsOrder[end_timing]" id="CsOrderEndTiming" class="form-control"
                            value="{{ old('end_timing', $formattedDate) }}">
                    </div>
                </div>
            </fieldset>

            <input type="hidden" name="CsOrder[id]" value="{{ $orderId }}">
        </form>
    @endif
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary" onclick="changeEndTiming()">Process</button>
</div>