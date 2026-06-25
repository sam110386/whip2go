function loadPaymentPlans(lease_id){
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
        css: {'z-index': '9999'}
    });
    $.post(SITE_URL + "admin/prepaid_plan/prepaid_plans/loadBookingPlans", {lease_id:lease_id}, function (data) {
        if(data.status){
            $("#capturepaypopupprepaidplans").html(data.prepaidplan);
        }else{
            alert(data.message);
        }
    },'json').done(function(){
        jQuery.unblockUI();
    });
}
function changeInitialFeePaymentPlanStatus(planid,status,lease_id){
    var conf=confirm("Are you sure you want to change status of this record?");
    if(!conf){
        return false;
    }
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
        css: {'z-index': '9999'}
    });
    $.post(SITE_URL + "admin/prepaid_plan/prepaid_plans/changestatus", {planid:planid,status:status}, function (data) {
        alert(data.message);
    },'json').done(function(){
        jQuery.unblockUI();
        loadPaymentPlans(lease_id);
    });
}
function retryInitialFeePlanPayment(planid,lease_id){
    var conf=confirm("Are you sure you want to perform this action?");
    if(!conf){
        return false;
    }
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
        css: {'z-index': '9999'}
    });
    $.post(SITE_URL + "admin/prepaid_plan/prepaid_plans/retrypayment", {planid:planid}, function (data) {
        alert(data.message);
    },'json').done(function(){
        jQuery.unblockUI();
        loadPaymentPlans(lease_id)
    });
}


function ChargeAllInitialFeePlanPayments(bookingid){
    var conf=confirm("Are you sure you want to perform this action?");
    if(!conf){
        return false;
    }
    var selected=[];
    $("#capturepaypopupprepaidplans .PrepaidPlans").each(function(){
        if($(this).is(':checked')){
            selected.push($(this).val());
        }
    })
    if(selected.length==0){
        alert("Please select atleat one record");return false;
    }
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
        css: {'z-index': '9999'}
    });
    $.post(SITE_URL + "admin/prepaid_plan/prepaid_plans/chargeallpayments", {bookingid:bookingid,selected:selected}, function (data) {
        alert(data.message);
    },'json').done(function(){
        jQuery.unblockUI();
        loadPaymentPlans(bookingid)
    });
}
