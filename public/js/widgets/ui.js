function WidgetLogView(filename){
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Loading...</h1>',
        css: {'z-index': '9999'}
    });
    $.post(SITE_URL + "admin/widget_logs/display/"+filename, {}, function (data) {
        $("#myModal .modal-content").html(data);
        $ ("#myModal").modal("show").find(".modal-dialog").css("width", "100%").css("height", "100%");
    }).done(function(){
        jQuery.unblockUI();
    });
}

function WidgetLogDelete(filename){
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Loading...</h1>',
        css: {'z-index': '9999'}
    });
    $.post(SITE_URL + "admin/widget_logs/delete/"+filename, {}, function (data) {
        alert(data.message);
    },'json').done(function(){
        jQuery.unblockUI();
    });
}

function WidgetLogSubView(filename,ip){
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Loading...</h1>',
        css: {'z-index': '9999'}
    });
    $.post(SITE_URL + "admin/widget_logs/display_sub", {filename:filename,ip:ip}, function (data) {
        $("#plaidModal .modal-content").html(data);
        $ ("#plaidModal").modal("show").find(".modal-dialog").css("width", "100%").css("height", "100%");
    }).done(function(){
        jQuery.unblockUI();
    });
}