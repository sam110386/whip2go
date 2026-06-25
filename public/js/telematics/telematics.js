/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function showUIBlocker(ele) {
    $(ele).block({
        message: '<i class="icon-spinner4 spinner"></i>',
        overlayCSS: {
            backgroundColor: '#fff', opacity: 0.8, cursor: 'wait'
        },
        css: {border: 0, padding: 0, backgroundColor: 'transparent'}
    });
}


function addDevice(subid,deviceid){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"admin/telematics_sub_devices/add", {'subid':subid,deviceid:deviceid},function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html(data);
        $("#myModal").modal('show');
    });
}

function saveDevice(){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    if(!$("#AddDevice").valid()){
        jQuery.unblockUI();
        return false;
    }
    var fromdata=$("#AddDevice").serialize();
    $.post(SITE_URL+"admin/telematics_sub_devices/save", fromdata,function (resp) {
        jQuery.unblockUI();
        $("#myModal .modal-content .modal-body").html(resp.message);
        $("#myModal .modal-content .modal-footer").remove();
    },'json');
}

function openPayments(subid){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"admin/telematics_subscriptions/payments/"+subid,function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html('<div id="paymentlisting">'+data+'</div>');
        $("#myModal").modal('show').find('.modal-dialog').css('width','950px');
    });
}

function paymentRetry(paymentid){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"admin/telematics_subscriptions/paymentretry/",{paymentid:paymentid},function (data) {
        jQuery.unblockUI();
        alert(data.message);
    },'json');
}