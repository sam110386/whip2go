<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <form action="#" method="post" id="completeForm" class="form-horizontal">
        <legend class="text-semibold">Calculated Rental</legend>
        <div class="form-group">
            <label class="col-lg-4 control-label">Extend Order :</label>
            <div class="checkbox checkbox-switchery switchery-lg col-lg-2">
                <label>
                    <input type="checkbox" name="Text[autorenew]" id="TextAutorenew" class="switchery" {{ !empty($autorenew) ? "checked='checked'" : '' }}>
                </label>
            </div>
            <input type="hidden" name="Text[damage_fee]" class="number form-control inpt" value="{{ $all_fee['damage_fee'] ?? 0 }}">
            <input type="hidden" name="Text[uncleanness_fee]" class="number form-control inpt" value="{{ $all_fee['uncleanness_fee'] ?? 0 }}">
            <div class="col-lg-6">
                <input type="text" name="Text[autorenewenddate]" id="TextAutorenewenddate" class="form-control" value="{{ $end_datetime ?? '' }}">
            </div>
        </div>
        <div class="form-group"><label class="col-lg-4 control-label">Estimated Usage Charges :</label><div class="col-lg-8"><input type="text" name="Text[estimated_rent]" class="number form-control" readonly value="{{ $all_fee['estimated_rent'] ?? 0 }}"></div></div>
        <div class="form-group"><label class="col-lg-4 control-label">Discount :</label><div class="col-lg-8"><input type="text" name="Text[discount]" class="number form-control discount" value="{{ $all_fee['discount'] ?? 0 }}"></div></div>
        <div class="form-group"><label class="col-lg-4 control-label">Usage Charges :</label><div class="col-lg-8"><input type="text" name="Text[rent]" class="number form-control inpt" value="{{ $all_fee['rent'] ?? 0 }}"></div></div>
        <div class="form-group"><label class="col-lg-4 control-label">Tax :</label><div class="col-lg-8"><input type="text" name="Text[tax]" class="number form-control inpt" value="{{ $all_fee['tax'] ?? 0 }}"></div></div>
        <div class="form-group"><label class="col-lg-4 control-label">DIA Fee :</label><div class="col-lg-8"><input type="text" name="Text[dia_fee]" class="number form-control inpt" value="{{ $all_fee['dia_fee'] ?? 0 }}"></div></div>
        <div class="form-group"><label class="col-lg-4 control-label">EMF Fee :</label><div class="col-lg-8"><input type="text" name="Text[extra_mileage_fee]" class="number form-control inpt" value="{{ $all_fee['extra_mileage_fee'] ?? 0 }}"></div></div>
        <div class="form-group"><label class="col-lg-4 control-label">EMF Tax :</label><div class="col-lg-8"><input type="text" name="Text[emf_tax]" class="number form-control inpt" value="{{ $all_fee['emf_tax'] ?? 0 }}"></div></div>
        <div class="form-group"><label class="col-lg-4 control-label">Lateness Fee :</label><div class="col-lg-8"><input type="text" name="Text[lateness_fee]" class="number form-control inpt" value="{{ $all_fee['lateness_fee'] ?? 0 }}"></div></div>
        <div class="form-group"><label class="col-lg-4 control-label">Grand Total :</label><div class="col-lg-8" id="gdtotal">{{ number_format((float)($all_fee['rent'] ?? 0) + (float)($all_fee['tax'] ?? 0) + (float)($all_fee['extra_mileage_fee'] ?? 0) + (float)($all_fee['lateness_fee'] ?? 0) + (float)($all_fee['dia_fee'] ?? 0), 2, '.', '') }}</div></div>
        <div class="form-group"><label class="col-lg-4 control-label">Scheduled Fee :</label><div class="col-lg-8"><input type="text" name="Text[initial_fee]" class="number form-control" value="{{ $all_fee['initial_fee'] ?? 0 }}"></div></div>
        <div class="form-group"><label class="col-lg-4 control-label">Scheduled Fee Tax:</label><div class="col-lg-8"><input type="text" name="Text[initial_fee_tax]" class="number form-control" value="{{ $all_fee['initial_fee_tax'] ?? 0 }}"></div></div>
        <div class="form-group"><label class="col-lg-4 control-label">Scheduled Fee Total:</label><div class="col-lg-8"><input type="text" name="Text[initial_fee_total]" class="number form-control" value="{{ (float)($all_fee['initial_fee'] ?? 0) + (float)($all_fee['initial_fee_tax'] ?? 0) }}"></div></div>
        <div class="form-group"><label class="col-lg-4 control-label">Insurance Fee :</label><div class="col-lg-8"><input type="text" name="Text[insurance_fee]" class="number form-control" value="{{ $all_fee['insurance_amt'] ?? 0 }}"></div></div>
        <div class="form-group"><label class="col-lg-4 control-label">DIA Ins Add On :</label><div class="col-lg-8"><input type="text" name="Text[dia_insu]" class="number form-control inpt" value="{{ $all_fee['dia_insu'] ?? 0 }}"></div></div>
        <div class="form-group"><label class="col-lg-4 control-label">Pending Toll :</label><div class="col-lg-8"><input type="text" name="Text[pending_toll]" class="number form-control" value="{{ $all_fee['pending_toll'] ?? 0 }}"></div></div>
        <div class="form-group"><label class="col-lg-4 control-label">Special Note :</label><div class="col-lg-8"><input type="text" name="Text[details]" class="form-control" value=""></div></div>
        <div class="form-group"><label class="col-lg-4 control-label">Renew but don't charge :</label><div class="col-lg-8"><input type="checkbox" name="Text[renew_but_dont_charge]" class="switchery pull-left" value="1"></div></div>
        <div class="form-group"><label class="col-lg-4 control-label text-danger">Calculated Penalty Insurance :</label><div class="col-lg-8"><input type="text" name="Text[insurance_penalty]" class="form-control" value="{{ $calculatedInsurance ?? 0 }}"></div></div>
        <input type="hidden" id="TextOrderid" name="Text[orderid]" value="{{ $orderid }}">
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary" onclick="processComplete(this)">Process</button>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $("#completeForm .inpt").on("keyup", function () {
            var total = 0;
            $("#completeForm .inpt").each(function () {
                var val = parseFloat($(this).val());
                total += isNaN(val) ? 0 : val;
            });
            $("#gdtotal").html(total.toFixed(2));
        });
        if ($("#TextAutorenewenddate").length && $.fn.datetimepicker) {
            $("#TextAutorenewenddate").datetimepicker({ format: "YYYY-MM-DD hh:mm A" });
        }
        $("#TextAutorenew").on("click", function () {
            if ($(this).is(":checked") && typeof completeBooking === "function") {
                completeBooking($("#TextOrderid").val(), 1);
            }
        });
    });
</script>

