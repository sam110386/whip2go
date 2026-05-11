/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function bookingDetail(bookingid){
   jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"admin/accounting/reports/booking", {'orderid':bookingid},function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html(data);
        $("#myModal").modal('show').find('.modal-dialog').css('width','750px');;
    });
}


function payoutDetail(payoutid){
   jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"admin/accounting/reports/payout", {'payoutid':payoutid},function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html(data);
        $("#myModal").modal('show').find('.modal-dialog').css('width','750px');;
    });
}

function transactionDetail(transaction){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"admin/accounting/reports/transaction", {'transaction':transaction},function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html(data);
        $("#myModal").modal('show').find('.modal-dialog').css('width','750px');;
    });
}

