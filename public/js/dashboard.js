/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function saleStaticsDateChange(ele) {
    $("#salestatics").block({
        message: '<i class="icon-spinner4 spinner"></i>',
        overlayCSS: {
            backgroundColor: '#fff', opacity: 0.8, cursor: 'wait'
        },
        css: {border: 0, padding: 0, backgroundColor: 'transparent'}
    });
    $.post(SITE_URL + "dashboard/loadsalestatics", {key: $(ele).val()}, function (resp) {
        if (resp.status) {
            $("#salestatics").html(resp.view);
        }
    },'json').done(function () {
        $("#salestatics").unblock();
    });
}
function loadVehicleSummary(ele) {
    ele.block({
        message: '<i class="icon-spinner4 spinner"></i>',
        overlayCSS: {
            backgroundColor: '#fff', opacity: 0.8, cursor: 'wait'
        },
        css: {border: 0, padding: 0, backgroundColor: 'transparent'}
    });
    $.post(SITE_URL + "dashboard/loadvehiclesummary", {}, function (resp) {
        if (resp.status) {
            ele.html(resp.view);
        }
    },'json').done(function () {
        ele.unblock();
    });
}
function loadBookingSummary(ele) {
    ele.block({
        message: '<i class="icon-spinner4 spinner"></i>',
        overlayCSS: {
            backgroundColor: '#fff', opacity: 0.8, cursor: 'wait'
        },
        css: {border: 0, padding: 0, backgroundColor: 'transparent'}
    });
    $.post(SITE_URL + "dashboard/loadbookingsummary", {}, function (resp) {
        if (resp.status) {
            ele.html(resp.view);
        }
    },'json').done(function () {
        ele.unblock();
    });
}
function loadVehicleReport(ele) {
    ele.block({
        message: '<i class="icon-spinner4 spinner"></i>',
        overlayCSS: {
            backgroundColor: '#fff', opacity: 0.8, cursor: 'wait'
        },
        css: {border: 0, padding: 0, backgroundColor: 'transparent'}
    });
    $.post(SITE_URL + "dashboard/loadvehiclereport", {}, function (resp) {
        if (resp.status) {
            ele.html(resp.view);
        }
    },'json').done(function () {
        ele.unblock();
        // Hide if collapsed by default
        $('.panelwraper .panel-collapsed').children('.panel-heading').nextAll().hide();
        // Rotate icon if collapsed by default
        $('panelwraper .panel-collapsed').find('[data-action=collapse]').children('i').addClass('rotate-180');
        // Collapse on click
        $('.panelwraper [data-action=collapse]').click(function (e) {
            e.preventDefault();
            var $panelCollapse = $(this).parent().parent().parent().parent().nextAll();
            $(this).parents('.panelwraper').toggleClass('panel-collapsed');
            $(this).toggleClass('rotate-180');
            $panelCollapse.slideToggle(150);
        });
    });
}
$(function(){
    saleStaticsDateChange($("#saleStaticsDate"));
    loadVehicleSummary($("#vehiclesummary"));
    loadBookingSummary($("#bookingsummary"));
    loadVehicleReport($("#vehiclereport"));
    
});

