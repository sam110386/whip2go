function chargeInsuranceInAdvance(orderruleid){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"admin/payers/charge_advance", {'orderruleid':orderruleid},function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html(data);
        $("#myModal").modal('show').find('.modal-dialog').css('width','650px');;
    }).done(function(){
        $("#InsurancePayerDays").keyup(function(){
            $("#amountocharge").html('$'+($(this).val() * $("#InsurancePayerDailyRate").val()).toFixed(2));
        });
    });
}


function processInsuranceInAdvanceCharge(){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    let params=$("#InsurancePayerAdminChargeAdvanceForm").serialize();
    $.post(SITE_URL+"admin/payers/process_charge_advance", params,function (data) {
        jQuery.unblockUI();
        alert(data.message);
        if(data.status){
            $("#myModal").modal('hide'); 
        }
    },'json');
}
//Function used, in inprogress page

function pendingInsurancePopup(order,ruleid){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"admin/payers/pendinginsurancepopup", {'order':order,'ruleid':ruleid},function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html(data);
        $("#myModal").modal('show').find('.modal-dialog').css('width','850px');
    }).done(function(){
        $("#InsurancePayerAdminPendinginsurancepopupForm").validate();
        $.post(SITE_URL+"admin/payers/usertransactions/"+ruleid, {},function (data) {
            $("#transsactionlisting").html(data);
        });
        
    });
}

    function saveBOIPendingInsurance(order,ruleid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        if(!$("#InsurancePayerAdminPendinginsurancepopupForm").valid()){
            return false;
        }
        var params=$("#InsurancePayerAdminPendinginsurancepopupForm").serialize();
        $.post(SITE_URL+"admin/payers/process_boyi_insurance", params,function (data) {
            alert(data.message);
            if(data.status){
                $("#myModal").modal('hide');
            }
        }).done(function(){
            jQuery.unblockUI();
            
        });
}