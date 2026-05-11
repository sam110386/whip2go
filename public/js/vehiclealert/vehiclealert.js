function DeleteVehicleAlert(recordid){
    var conf=confirm("Are you sure you want to delete this record?");
    if(!conf){
        return false;
    }
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
        css: {'z-index': '9999'}
    });
    $.post(SITE_URL + "admin/vehicle_alert/vehicle_alerts/delete", {recordid:recordid}, function (data) {
        alert(data.message);
        if(data.status){
            $("tr#row_"+recordid).remove();
        }
    },'json').done(function(){
        jQuery.unblockUI();
    });
}