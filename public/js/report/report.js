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
    
function customerReportRefresh(rowid) {
    var ele = $("#postsPaging table tbody tr#" + rowid);
    showUIBlocker(ele);
    
    $.post(SITE_URL + "report/customers/refresh", {'rowid': rowid}, function (resp) {
        if (resp.status) {
            ele.html(resp.result);

        } else {
            alert(resp.message);
        }
    }, 'json').done(function () {
        $(ele).unblock();
    });
}

/****Admin page function***/
function AdmincustomerReportRefresh(rowid) {
    var ele = $("#postsPaging table tbody tr#" + rowid);
    showUIBlocker(ele);
    
    $.post(SITE_URL + "admin/report/customers/refresh", {'rowid': rowid}, function (resp) {
        if (resp.status) {
            ele.html(resp.result);

        } else {
            alert(resp.message);
        }
    }, 'json').done(function () {
        $(ele).unblock();
    });
}

function ShowPastDueLogs(order,all=false){
    if(!order) return;
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"admin/report/pastdues/logs", {'order':order,all:all},function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html(data);
        $("#myModal").modal('show');
    });
}
/******cloud page function ***/

function CloudcustomerReportRefresh(rowid) {
    var ele = $("#postsPaging table tbody tr#" + rowid);
    showUIBlocker(ele);
    
    $.post(SITE_URL + "cloud/report/customers/refresh", {'rowid': rowid}, function (resp) {
        if (resp.status) {
            ele.html(resp.result);

        } else {
            alert(resp.message);
        }
    }, 'json').done(function () {
        $(ele).unblock();
    });
}

function CloudShowPastDueLogs(order){
    if(!order) return;
    
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"cloud/report/pastdues/logs", {'order':order},function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html(data);
        $("#myModal").modal('show');
    });
}
   