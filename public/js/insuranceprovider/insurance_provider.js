function OpenBoyiByDIAListPopUp(bookingid){
    jQuery.blockUI({ message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Just a moment...</h1>' });
    jQuery.post(SITE_URL+"admin/insuprovider/quotes/listpopup", {bookingid:bookingid}, function (data) {
        jQuery.unblockUI();
        $("#plaidModal .modal-content").html(data);
        $("#plaidModal").modal('show').find('.modal-dialog').css('width','850px');
    }).done(function(){
        jQuery.unblockUI();
    });
    return false;
}

function OpenBoyiByDIAPopUp(bookingid,id=''){
    jQuery.blockUI({ message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Just a moment...</h1>' });
    jQuery.post(SITE_URL+"admin/insuprovider/quotes/popup", {bookingid:bookingid,id:id}, function (data) {
        jQuery.unblockUI();
        $("#statementModal .modal-content").html(data);
        $("#statementModal").modal('show').find('.modal-dialog').css('width','650px');
    }).done(function(){
        jQuery.unblockUI();
		jQuery("#statementModal").on("hidden.bs.modal", function () {
			OpenBoyiByDIAListPopUp(bookingid);
		});
    });
    return false;
}

function DeleteBoyiByDIAPopUp(bookingid,id=''){
    jQuery.blockUI({ message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Just a moment...</h1>' });
    jQuery.post(SITE_URL+"admin/insuprovider/quotes/delete", {bookingid:bookingid,id:id}, function (data) {
        jQuery.unblockUI();
		if(!data.status){
			alert(data.message);
		}
    },'json').done(function(){
        jQuery.unblockUI();
		OpenBoyiByDIAListPopUp(bookingid);
    });
    return false;
}
 
function SaveInsuranceProviderQuotePopUp() {
	if ($("#InsuranceQuoteAdminPopupForm").valid()) {
		jQuery.blockUI({
			message:
				'<h1><img src="' +
				SITE_URL +
				'img/select2-spinner.gif" /> Saving...</h1>',
		});
		var data = new FormData($("#InsuranceQuoteAdminPopupForm").get(0));
		$.ajax({
			url: SITE_URL + "admin/insuprovider/quotes/save",
			type: "post",
			dataType: "JSON",
			data: data,
			processData: false,
			contentType: false,
			success: function (data, status) {
				if (!data.status) {
					alert(data.message);
					return;
				}
				$("#statementModal").modal("hide");
			},
			complete: function () {
				jQuery.unblockUI();
			},
		});
	}
}
