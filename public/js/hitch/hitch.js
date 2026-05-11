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

/******cloud page function ***/

function refreshLead(leadid) {
    var ele = $("#postsPaging");
    showUIBlocker(ele);
    $.post(SITE_URL + "admin/hitch/leads/refresh", {'leadid': leadid}, function (resp) {
        if (resp.status) {
            alert(resp.message);
        } else {
            alert(resp.message);
        }
    }, 'json').done(function () {
        $(ele).unblock();
    });
}

    
function customerReportRefresh(rowid) {
    var ele = $("#postsPaging table tbody tr#" + rowid);
    showUIBlocker(ele);
    
    $.post(SITE_URL + "cloud/hitch/customer_reports/refresh", {'rowid': rowid}, function (resp) {
        if (resp.status) {
            ele.html(resp.result);

        } else {
            alert(resp.message);
        }
    }, 'json').done(function () {
        $(ele).unblock();
    });
}

/***report page js functions***/
    function openTripDetails(tripId) {
        jQuery.blockUI({ message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Just a moment...</h1>' });
        jQuery.post(SITE_URL+"cloud/hitch/hitch_reports/details/" + tripId, {}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width','750px');
        });
        jQuery.unblockUI();
        return false;
    }
    
    function loadsubbooking(orderid){
        var havingchild=jQuery("tr#tr_"+orderid).attr('rel-parent');
        if(havingchild=='yes'){
            jQuery("tbody tr.child_"+orderid).each(function(){
                jQuery(this).remove();
            });
            jQuery("tr#tr_"+orderid).attr('rel-parent','no');
            return false;
        }
        jQuery.blockUI({ message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Just a moment...</h1>' });
        jQuery.post(SITE_URL+"cloud/hitch/hitch_reports/loadsubbooking/" + orderid, {}, function (data) {
            if(data.status=='success'){
               jQuery("tr#tr_"+data.booking_id).after(data.data); 
               jQuery("tr#tr_"+data.booking_id).attr('rel-parent','yes');
            }
            jQuery.unblockUI();
        },'json');
        
        return false;
    }
    
    //open combined booking details
    function openCombinedBookingDetails(tripId) {
        jQuery.blockUI({ message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Just a moment...</h1>' });
        jQuery.post(SITE_URL+"cloud/hitch/hitch_reports/autorenewddetails/" + tripId, {}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width','850px');
        });
        jQuery.unblockUI();
        return false;
    }
    
    function reviewimages(orderid){
        jQuery.blockUI({ message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Just a moment...</h1>' });
        jQuery.post(SITE_URL+"cloud/booking_reviews/reviewimages/" + orderid, {}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show');
        });
        jQuery.unblockUI();
        return false;
    }
    /**for agreement PDF**/
    function getagreement(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/hitch/hitch_reports/getagreement", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            if(!data.status){
                alert(data.message);
            }else{
                window.open(data.result.file);
            }
        });
    }

    function cloudOpenBookingDetails(order){
        jQuery.blockUI({ message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Just a moment...</h1>' });
        jQuery.post(SITE_URL+"cloud/report/pastdues/details", {order:order}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width','850px');
        });
        jQuery.unblockUI();
        return false;
    }