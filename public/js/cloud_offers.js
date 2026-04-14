const dateFormat = "mm/dd/yyyy";

function rent_opt(v) {
    var elem = parseInt($("#rent_opt").parent("#panelbody").attr('rel-rental'));
    if (v) {
        if (elem === 30) {
            alert("Sorry, you cant add more than 30 records");
            return;
        }
        elem++;
        var element = '<div class="form-group" id="ele-' + elem + '">' +
                '<label class="col-lg-2 control-label">&nbsp;</label>' +
                '<div class="col-lg-2 control-label">After Days</div>' +
                '<div class="col-lg-1 controllabel"><i class="icon-calendar3 icon-2x"></i></div>' +
                '<div class="col-lg-3 calwrap">' +
                '<input name="data[VehicleOffer][rent_opt][' + elem + '][after_day]" class="date form-control" placeholder="days" value="0" type="text">' +
                '</div>' +
                '<div class="col-lg-1">Amount</div>' +
                '<div class="col-lg-2"><input name="data[VehicleOffer][rent_opt][' + elem + '][amount]" class="form-control" placeholder="amount" value="0" type="text"></div>' +
                '<div class="col-lg-1"><a href="javascript:;" onclick="rent_opt(false)"><i class=" icon-minus-circle2 icon-2x"></i></a></div></div>';
        $("#rent_opt").append(element);
        $(".date").datepicker({format: dateFormat,startDate:$("#VehicleOfferTempdatetime").val()});
    } else {
        $("#rent_opt #ele-" + elem).remove();
        elem--;
    }
    $("#rent_opt").parent("#panelbody").attr('rel-rental', elem);
}
function deposit_opt(v) {
    var elem = parseInt($("#deposit_opt").parent("#panelbody").attr('rel-deposit'));
    if (v) {
        if (elem === 30) {
            alert("Sorry, you cant add more than 30 records");
            return;
        }
        elem++;
        var element = '<div class="form-group" id="ele-' + elem + '">' +
                '<label class="col-lg-2 control-label">&nbsp;</label>' +
                '<div class="col-lg-2 control-label">After Days</div>' +
                '<div class="col-lg-1 controllabel"><i class="icon-calendar3 icon-2x"></i></div>' +
                '<div class="col-lg-3 calwrap">' +
                '<input name="data[VehicleOffer][deposit_opt][' + elem + '][after_day_date]" class="date form-control" placeholder="days" value="" type="text">' +
                '</div>' +
                '<div class="col-lg-1">Amount</div>' +
                '<div class="col-lg-2"><input name="data[VehicleOffer][deposit_opt][' + elem + '][amount]" class="form-control" placeholder="amount" value="0" type="text"></div>' +
                '<div class="col-lg-1"><a href="javascript:;" onclick="deposit_opt(false)"><i class=" icon-minus-circle2 icon-2x"></i></a></div></div>';
        $("#deposit_opt").append(element);
        $(".date").datepicker({format: dateFormat,startDate:$("#VehicleOfferTempdatetime").val()});
    } else {
        $("#deposit_opt #ele-" + elem).remove();
        elem--;
    }
    $("#deposit_opt").parent("#panelbody").attr('rel-deposit', elem);
}
function initialfee_opt(v) {
    var elem = parseInt($("#initialfee_opt").parent("#panelbody").attr('rel-initialfee'));
    if (v) {
        if (elem === 30) {
            alert("Sorry, you cant add more than 30 records");
            return;
        }
        elem++;
        var element = '<div class="form-group" id="ele-' + elem + '">' +
                '<label class="col-lg-2 control-label">&nbsp;</label>' +
                '<div class="col-lg-2 control-label">After Days</div>' +
                '<div class="col-lg-1 controllabel"><i class="icon-calendar3 icon-2x"></i></div>' +
                '<div class="col-lg-3 calwrap">' +
                '<input name="data[VehicleOffer][initial_fee_opt][' + elem + '][after_day_date]" class="date form-control" placeholder="days" value="" type="text">' +
                '</div>' +
                '<div class="col-lg-1">Amount</div>' +
                '<div class="col-lg-2"><input name="data[VehicleOffer][initial_fee_opt][' + elem + '][amount]" class="form-control" placeholder="amount" value="0" type="text"></div>' +
                '<div class="col-lg-1"><a href="javascript:;" onclick="initialfee_opt(false)"><i class=" icon-minus-circle2 icon-2x"></i></a></div></div>';
        $("#initialfee_opt").append(element);
        $(".date").datepicker({format: dateFormat,startDate:$("#VehicleOfferTempdatetime").val()});
    } else {
        $("#initialfee_opt #ele-" + elem).remove();
        elem--;
    }
    $("#initialfee_opt").parent("#panelbody").attr('rel-initialfee', elem);
}
function duration_opt(v) {
    var elem = parseInt($("#duration_opt").parent("#panelbody").attr('rel-duration'));
    if (v) {
        if (elem === 6) {
            alert("Sorry, you cant add more than 5 records");
            return;
        }
        elem++;
        var element = '<div class="form-group" id="ele-' + elem + '">' +
                '<label class="col-lg-4 control-label">Duration change After:</label>' +
                '<div class="col-lg-3">' +
                '<input name="data[VehicleOffer][duration_opt][' + elem + '][after_date]" class="date form-control" placeholder="Date" value="" type="text"></div>' +
                '<label class="col-lg-1 control-label">Duration:</label>' +
                '<div class="col-lg-3"><select name="data[VehicleOffer][duration_opt][' + elem + '][duration]" class="form-control">'+
                '<option value="1">1 day</option><option value="2">2 days</option><option value="3">3 days</option>'+
                '<option value="4">4 days</option><option value="5">5 days</option><option value="6">6 days</option>'+
                '<option value="7">7 days</option><option value="14">14 days</option><option value="30">30 days</option>'+
                '</select></div>' +
                '<div class="col-lg-1"><a href="javascript:;" onclick="duration_opt(false)"><i class=" icon-minus-circle2 icon-2x"></i></a></div></div>';
        $("#duration_opt").append(element);
        $(".date").datepicker({format: dateFormat,startDate:$("#VehicleOfferTempdatetime").val()});
    } else {
        $("#duration_opt #ele-" + elem).remove();
        elem--;
    }
    $("#duration_opt").parent("#panelbody").attr('rel-duration', elem);
}


$(".calendar, .date").datepicker({format: dateFormat});


$(document).ready(function () {
    $("#VehicleOfferGoal").change(function () {
        if ($(this).val() == '')
            return;

        if ($(this).val() === 'custom') {
            $("#VehicleOfferDownpayment").attr("readonly", false);
        } else {
            var down = $("#VehicleOfferTotalcost").val() * ($(this).val() / 100);
            $("#VehicleOfferDownpayment").val(parseFloat(down).toFixed(2));
            $("#VehicleOfferDownpayment").attr("readonly", true);
        }
    });
    $("#VehicleOfferPto").change(function () {
        if ($(this).val() === '')
            return;
        if ($(this).val() === '0') {
            $("#VehicleOfferDayRent").attr("readonly", false);
            //$("#calculateButton").attr("disabled", true);
            $("#VehicleOfferGoal").removeClass('required');
            $("#VehicleOfferDownpayment").removeClass('required');
            $("#VehicleOfferDays").removeClass('required');
        } else {
            $("#VehicleOfferDayRent").attr("readonly", true);
            $("#calculateButton").attr("disabled", false);
            $("#VehicleOfferGoal").addClass('required');
            $("#VehicleOfferDownpayment").addClass('required');
            $("#VehicleOfferDays").addClass('required');
        }
    });
    $("#VehicleOfferDuration").change(function () {
        if ($(this).val() === '')
            return;
        if ($(this).val() === 'custom') {
            $("#VehicleOfferDuration1").show();
            $("#VehicleOfferDuration1").removeClass('hidden');
            $("#VehicleOfferDuration1").addClass('required');
        } else {
            $("#VehicleOfferDuration1").hide();
            $("#VehicleOfferDuration1").addClass('hidden');
            $("#VehicleOfferDuration1").removeClass('required');
        }
    });

});

var calculatedObj=[];

function calculateFareMatrix() {
    if (!$("#VehicleOfferForm").valid({ignore: ':hidden'}))
        return;
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
        css: {'z-index': '9999'}
    });
    var formdata = $("#VehicleOfferForm").serialize();
    $.post(SITE_URL + 'cloud/vehicle_offers/getVehicleDynamicFareMatrix', formdata, function (obj) {
        jQuery.unblockUI();
        $("#VehicleOfferDays").val(obj.days);
        $("#VehicleOfferDayRent").val(obj.dayRent).removeClass('required').addClass('required');;
        $("#VehicleOfferInsurance").val(obj.dayInsurance);
        $("#VehicleOfferEmf").val(obj.emf);
        $("#VehicleOfferProgramFee").val(obj.program_fee);
        $("#VehicleOfferTotalInsurance").val(obj.total_insurance);
        $("#VehicleOfferTotalProgramCost").val(obj.total_program_cost);
        $("#VehicleOfferEquityshare").val(obj.equityshare);
        $("#VehicleOfferWriteDownAllocation").val(obj.write_down_allocation);
        $("#VehicleOfferFinanceAllocation").val(obj.finance_allocation);
        $("#VehicleOfferMaintenanceAllocation").val(obj.maintenance_allocation);
        $("#VehicleOfferDepreciationRate").val(obj.depreciation_rate);
        $("#VehicleOfferDispositionFee").val(obj.disposition_fee);
        $("#VehicleOfferCalculation").val(JSON.stringify(obj));
        //adjust duration to available max days
        if(obj.days<7){
            $("#VehicleOfferDuration").val(obj.days);
        }
        var duration=$("#VehicleOfferDuration").val();
        var rental=parseFloat(duration*obj.dayEmfRent).toFixed(2);
        var dia_fee=parseFloat((rental * parseFloat(obj.dia_rate)) / 100).toFixed(2);
        var tax=parseFloat((Math.round(rental*100)/100 + Math.round(dia_fee*100)/100) * parseFloat(obj.tax_rate)).toFixed(2);
        var total_rental=parseFloat(Math.round(rental*100)/100 + Math.round(dia_fee*100)/100 + Math.round(tax*100)/100).toFixed(2);
        var initial_fee= (parseFloat(obj.initial_fee)+(parseFloat(obj.initial_fee)* parseFloat(obj.tax_rate))).toFixed(2);
        var total_payable_today= (parseFloat(obj.deposit)+parseFloat(obj.initial_fee)+parseFloat(obj.initial_fee_tax)).toFixed(2);
        
        var program = '';
        program += '<ul>';
        program += '<li><strong>Adjusted Program Length:</strong> ' + obj.days + '(days)</li>';
        program += '<li><strong>Total Program Cost:</strong> ' + obj.total_program_cost + '</li>';
        program += '<li><strong>Program Fee:</strong> ' + obj.program_fee + '</li>';
        program += '<li><strong>Insurance Cost To Dealer:</strong> ' + obj.total_insurance + '</li>';
        program += '<li><strong>Day Insurance:</strong> ' + obj.dayInsurance + '</li>';
        program += '<li><strong>Day Rent:</strong> ' + obj.dayRent + '</li>';
        program += '<li><strong>EMF Per Day:</strong> ' + obj.emf + '</li>';
        program += '<li><strong>Day Rent With Emf:</strong> ' + obj.dayEmfRent + '</li>';
        program += '<li><strong>Monthly Miles:</strong> ' + obj.month_miles + '</li>';
        program += '<li><strong>Monthly EMF:</strong> ' + obj.month_emf + '</li>';
        program += '<li><strong>Weekly Rent:</strong> ' + obj.weekRent + '</li>';
        program += '<li><strong>Weekly EMF Rent:</strong> ' + obj.weekkEmfRent + '</li></ul>';
        
        program += '<p><strong>Payment Details For Booking Time Event</strong></p>';
        program += '<ul>';
        program += '<li><strong>Rent:</strong> ' + rental + '</li>';
        program += '<li><strong>Tax:</strong> ' + tax + '</li>';
        program += '<li><strong>DIA Fee:</strong> ' + dia_fee + '</li>';
        program += '<li><strong>Total Rental:</strong> ' + total_rental+ '</li>';
        program += '<li><strong>Deposit:</strong> ' + parseFloat(obj.deposit).toFixed(2) + '</li>';
        program += '<li><strong>Insurance:</strong> ' + insurance + '</li>';
        program += '<li><strong>Scheduled Payment:</strong> ' + parseFloat(initial_fee).toFixed(2) + '</li>';
        program += '<li><strong>Total Due today:</strong> ' + total_payable_today+ '</li></ul>';
        
        $("#calculations").html(program);
        $("#VehicleOfferForm button[type='submit']").prop("disabled",false);
        
        
    }, 'json');
}

function qualifyCheckr() {
    if ($("#VehicleOfferDriverPhone").val() == '') {
        alert("Please enter a valid phone#");
        return;
    }
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
        css: {'z-index': '9999'}
    });
    var formdata = $("#VehicleOfferForm").serialize();
    $.post(SITE_URL + 'cloud/vehicle_offers/qualify', formdata, function (resp) {
        jQuery.unblockUI();
        if (resp.status) {
            alert("Driver report is CLEAR");
        } else {
            alert(resp.message);
        }
    }, 'json');
}

$(document).ready(function () {
    $("#VehicleOfferForm").submit(function (event) {
        event.preventDefault(); //this will prevent the default submit
        var ele=$(this);
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        var formdata = $("#VehicleOfferForm").serialize();
        $.post(SITE_URL + 'cloud/vehicle_offers/qualifyIncome', formdata, function (resp) {
            if (!resp.status) {
                var conf=confirm(resp.message);
                if(conf){
                    ele.unbind('submit').submit();
                }else{
                   return false; 
                }
            }else{
               ele.unbind('submit').submit();
            }
        }, 'json').done(function(){
            jQuery.unblockUI();
        });
    });
});