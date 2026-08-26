function OpenIntercomActionPopUp(userid) {
    jQuery.blockUI({ message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Just a moment...</h1>' });
    jQuery.post(SITE_URL + "admin/intercom_popups/loadpopup", { userid: userid }, function (data) {
        jQuery.unblockUI();
        $("#myModal .modal-content").html(data);
        $("#myModal").modal('show').find('.modal-dialog').css('width', '1150px');
    }).done(function () {
        jQuery.unblockUI();
    });
    return false;
}

function callIntercomApiAction(url) {
    jQuery.blockUI({ message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Just a moment...</h1>' });

    jQuery.ajax({
        url: SITE_URL + url,
        type: 'get',
        data: {},
        headers: {
            'x-security': jQuery("#IntercomApisXtoken").val(),
            "Content-type": "application/json"
        },
        dataType: 'json',
        success: function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content #intercomapiresult").html(JSON.stringify(data));
        }
    }).done(function () {
        jQuery.unblockUI();
    });
    return false;
}


