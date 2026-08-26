function getBookingNotes(orderid) {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(SITE_URL + "admin/order_notes/loadhistory",{orderid: orderid},function (data) {
		$("#myModal .modal-content").html(data);
		$("#myModal").modal("show");
	}).done(function(){
        jQuery.unblockUI();
    });
}

function loadNewBookingNotesPopup(orderid,parentid) {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(SITE_URL + "admin/order_notes/loadnewnotepopup",{ orderid: orderid,parentid:parentid },function (data) {
		$("#myModal .modal-content").html(data);
		$("#myModal").modal("show").find(".modal-dialog").css("width", "800px");
	}).done(function(){
        jQuery.unblockUI();
    });
}

function saveBookingNote() {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	var data = jQuery("form#newbookingnotes").serialize();
	$.post(SITE_URL + "admin/order_notes/savenote",data,function (data) {
		if (data.status) {
			alert(data.message);
			$("#myModal").modal("hide");
		} else {
			alert(data.message);
		}
	},"json").done(function(){
        jQuery.unblockUI();
    });
}