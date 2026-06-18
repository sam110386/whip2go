<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <fieldset class="form-horizontal" id="starterWorkswrapper">
        <legend class="text-semibold">Vehicle Starter Test</legend>

        <div class="form-group">
            <label class="col-lg-4 control-label">&nbsp;</label>
            <div class="col-lg-8">
                <button type="button" id="DisableStaterInterrupt" class="focus_text btn no-margin"
                    onclick="DisableStaterInterrupt('{{ $vehicleid }}', '{{ $reservationid }}')">
                    Hit Disable
                </button>
            </div>
        </div>

        <div class="form-group starterWorkOptions" style="display: none;">
            <label class="col-lg-4 control-label">Does It Work?</label>
            <div class="col-lg-8">
                <button type="button" class="focus_text btn no-margin"
                    onclick="StaterInterruptWorks('{{ $vehicleid }}', '{{ $reservationid }}')">
                    Yes
                </button>
                <button type="button" class="focus_text btn no-margin"
                    onclick="javascript:$('#DisableStaterInterrupt').html('Retry Stater Disable');">
                    No
                </button>
            </div>
        </div>

        <div class="form-group starterWorks" style="display: none;">
            <label class="col-lg-4 control-label">&nbsp;</label>
            <div class="col-lg-8">
                <button type="button" id="EnableStaterInterrupt" class="focus_text btn no-margin"
                    onclick="EnableStaterInterrupt('{{ $vehicleid }}', '{{ $reservationid }}')">
                    Let's Make Starter Enable
                </button>
            </div>
        </div>

        <div class="form-group starterEnableWorkOptions" style="display: none;">
            <label class="col-lg-4 control-label">Does It Work?</label>
            <div class="col-lg-8">
                <button type="button" class="focus_text btn no-margin"
                    onclick="javascript:$('#starterWorkswrapper').html('Great!!, it works');">
                    Yes
                </button>
                <button type="button" class="focus_text btn no-margin"
                    onclick="javascript:$('#EnableStaterInterrupt').html('Retry Stater Enable');">
                    No
                </button>
            </div>
        </div>
    </fieldset>
</div>