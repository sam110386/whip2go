<div class="panel">
    <script type="text/javascript">
        jQuery(document).ready(function() {
            jQuery("#payoutfrmadmin").validate();
        });
    </script>
    <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;padding: 13px 23px 0;">
        <h3 style="width: 80%; float: left;">Update Payout Schedule</h3>
    </section>

    <div class="row">
        @includeif('common.flash-messages')
    </div>

    <div class="row">
        @if(!empty($Loadeddata))
        <fieldset class="col-lg-12">
            <form action="#" method="POST" name="payoutfrmadmin" id="payoutfrmadmin" class="form-horizontal">
                @csrf
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Frequency :<span class="text-danger">*</span></label>
                        <div class="col-lg-8">
                            <select name="frequency" id="UserFrequency" class="form-control required">
                                <option value="daily" {{ ($Loadeddata['payout_schedule']['interval'] ?? '') == 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ ($Loadeddata['payout_schedule']['interval'] ?? '') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="monthly" {{ ($Loadeddata['payout_schedule']['interval'] ?? '') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" id="delay">
                        <label class="col-lg-4 control-label">Delay (In days) :<span class="text-danger">*</span></label>
                        <div class="col-lg-8">
                            <select name="delay" class="form-control required">
                                @foreach(range(1, 30) as $val)
                                <option value="{{ $val }}" {{ ($Loadeddata['payout_schedule']['delay_days'] ?? '') == $val ? 'selected' : '' }}>{{ $val }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group" id="weekly_anchor">
                        <label class="col-lg-4 control-label">Day :<span class="text-danger">*</span></label>
                        <div class="col-lg-8">
                            <select name="weekly_anchor" class="form-control required">
                                @php
                                $weekdays = [
                                'monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 'sunday' => 'Sunday'
                                ];
                                @endphp
                                @foreach($weekdays as $key => $label)
                                <option value="{{ $key }}" {{ ($Loadeddata['payout_schedule']['weekly_anchor'] ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group" id="monthly_anchor">
                        <label class="col-lg-4 control-label">Day :<span class="text-danger">*</span></label>
                        <div class="col-lg-8">
                            <select name="monthly_anchor" class="form-control required">
                                @foreach(range(1, 30) as $val)
                                <option value="{{ $val }}" {{ ($Loadeddata['payout_schedule']['monthly_anchor'] ?? '') == $val ? 'selected' : '' }}>{{ $val }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            <button type="button" class="btn btn-primary" onClick="savePayoutSchedule()">Update</button>
                            <button type="button" class="btn btn-cancel left-margin" onClick="$('#myModal').modal('hide');">Cancel</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="token" value="{{ $token ?? '' }}">
            </form>
        </fieldset>

        <script type="text/javascript">
            $(document).ready(function() {
                function togglePayoutFields(frequency) {
                    if (frequency == 'daily') {
                        $("#payoutfrmadmin #delay").show();
                        $("#payoutfrmadmin #weekly_anchor").hide();
                        $("#payoutfrmadmin #monthly_anchor").hide();
                    } else if (frequency == 'weekly') {
                        $("#payoutfrmadmin #delay").hide();
                        $("#payoutfrmadmin #weekly_anchor").show();
                        $("#payoutfrmadmin #monthly_anchor").hide();
                    } else if (frequency == 'monthly') {
                        $("#payoutfrmadmin #delay").hide();
                        $("#payoutfrmadmin #weekly_anchor").hide();
                        $("#payoutfrmadmin #monthly_anchor").show();
                    }
                }

                togglePayoutFields($("#payoutfrmadmin #UserFrequency").val());

                $("#payoutfrmadmin #UserFrequency").change(function() {
                    togglePayoutFields($(this).val());
                });
            });
        </script>
        @else
        <div class="col-lg-12">
            <p>No payout data found or Stripe Plugin is pending integration.</p>
        </div>
        @endif
    </div>
</div>