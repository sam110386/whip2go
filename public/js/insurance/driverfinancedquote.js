function OpenDriverFinancedInsuranceQuoteUploadPopUp(recordid, model = "myModal") {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery.post(SITE_URL + "admin/insurance/driver_financed_quotes/popup",{ recordid: recordid, model: model },function (data) {
			$("#" + model + " .modal-content").html(data);
			$("#" + model).modal("show").find(".modal-dialog").css("width", "850px");
	}).done(function () {
		jQuery.unblockUI();
		
		$("#DriverFinancedInsuranceQuoteDeclarationDoc").fileinput({
			browseLabel: "Browse",
			browseIcon: '<i class="icon-file-plus"></i>',
			uploadIcon: '<i class="icon-file-upload2"></i>',
			removeIcon: '<i class="icon-cross3"></i>',
			layoutTemplates: {
				icon: '<i class="icon-file-check"></i>',
			},
			uploadUrl: SITE_URL + "admin/insurance/driver_financed_quotes/saveImage", // server upload action
			uploadAsync: true,
			maxFileCount: 1,
			deleteUrl: SITE_URL + "admin/insurance/driver_financed_quotes/deleteImage",
			allowedFileExtensions: ["jpeg", "jpg", "png", "pdf"],
			overwriteInitial: false,
			maxFileSize: 10024,
			uploadExtraData: { id: recordid, type: "declaration_doc" },
			showCancel: false,
			showRemove: false,
		});
		$("#DriverFinancedInsuranceQuoteCard").fileinput({
			browseLabel: "Browse",
			browseIcon: '<i class="icon-file-plus"></i>',
			uploadIcon: '<i class="icon-file-upload2"></i>',
			removeIcon: '<i class="icon-cross3"></i>',
			layoutTemplates: {
				icon: '<i class="icon-file-check"></i>',
			},
			uploadUrl: SITE_URL + "admin/insurance/driver_financed_quotes/saveImage", // server upload action
			uploadAsync: true,
			maxFileCount: 1,
			deleteUrl: SITE_URL + "admin/insurance/driver_financed_quotes/deleteImage",
			allowedFileExtensions: ["jpeg", "jpg", "png", "pdf"],
			overwriteInitial: false,
			maxFileSize: 10024,
			uploadExtraData: { id: recordid, type: "insurance_card" },
			showCancel: false,
			showRemove: false,
		});
		
		$("#DriverFinancedCreditCardAdminPopupForm").validate();
		$('#DriverFinancedCreditCardCardNumber').mask("0000-0000-0000-0000",{placeholder: "Valid card number"});
		$('#DriverFinancedCreditCardExpDate').mask("00 / 00",{placeholder: "MM / YY"});
		$('#DriverFinancedCreditCardCvv').mask("0000",{placeholder: "CVC"});

		$(".date").datetimepicker({
			useCurrent: false, //Important! See issue #1075
			format: "YYYY-MM-DD",
		});
	});
	return false;
}

function OpenDriverFinancedQuotePopUpFromBooking(id, model = "myModal") {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery.post(SITE_URL + "admin/insurance/driver_financed_quotes/popup",{ id: id, model: model },function (data) {
			$("#" + model + " .modal-content").html(data.html);
			$("#" + model).modal("show").find(".modal-dialog").css("width", "850px");
	},'json').done(function () {
		jQuery.unblockUI();
		$("#DriverFinancedInsuranceQuoteDeclarationDoc").fileinput({
			browseLabel: "Browse",
			browseIcon: '<i class="icon-file-plus"></i>',
			uploadIcon: '<i class="icon-file-upload2"></i>',
			removeIcon: '<i class="icon-cross3"></i>',
			layoutTemplates: {
				icon: '<i class="icon-file-check"></i>',
			},
			uploadUrl: SITE_URL + "admin/insurance/driver_financed_quotes/saveImage", // server upload action
			uploadAsync: true,
			maxFileCount: 1,
			deleteUrl: SITE_URL + "admin/insurance/driver_financed_quotes/deleteImage",
			allowedFileExtensions: ["jpeg", "jpg", "png", "pdf"],
			overwriteInitial: false,
			maxFileSize: 10024,
			uploadExtraData: { id: data.recordid, type: "declaration_doc" },
			showCancel: false,
			showRemove: false,
		});
		$("#DriverFinancedInsuranceQuoteCard").fileinput({
			browseLabel: "Browse",
			browseIcon: '<i class="icon-file-plus"></i>',
			uploadIcon: '<i class="icon-file-upload2"></i>',
			removeIcon: '<i class="icon-cross3"></i>',
			layoutTemplates: {
				icon: '<i class="icon-file-check"></i>',
			},
			uploadUrl: SITE_URL + "admin/insurance/driver_financed_quotes/saveImage", // server upload action
			uploadAsync: true,
			maxFileCount: 1,
			deleteUrl: SITE_URL + "admin/insurance/driver_financed_quotes/deleteImage",
			allowedFileExtensions: ["jpeg", "jpg", "png", "pdf"],
			overwriteInitial: false,
			maxFileSize: 10024,
			uploadExtraData: { id: data.recordid, type: "insurance_card" },
			showCancel: false,
			showRemove: false,
		});
		
		$("#DriverFinancedCreditCardAdminPopupForm").validate();
		$('#DriverFinancedCreditCardCardNumber').mask("0000-0000-0000-0000",{placeholder: "Valid card number"});
		$('#DriverFinancedCreditCardExpDate').mask("00 / 00",{placeholder: "MM / YY"});
		$('#DriverFinancedCreditCardCvv').mask("0000",{placeholder: "CVC"});

		$(".date").datetimepicker({
			useCurrent: false, //Important! See issue #1075
			format: "YYYY-MM-DD",
		});
	});
	return false;
}

function SaveDriverFinancedInsuranceQuoteUploadPopUp(model = "myModal",approve=false) {
	if ($("#DriverFinancedInsuranceQuoteAdminPopupForm").valid()) {
		jQuery.blockUI({
			message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Just a moment...</h1>',
		});
        var oData = $("#DriverFinancedInsuranceQuoteAdminPopupForm").serialize()+ "&approve="+ approve;
        jQuery.post(SITE_URL + "admin/insurance/driver_financed_quotes/save",oData,function (data) {
			$("#" + model).modal("hide");
		},'json').done(function () {
			jQuery.unblockUI();
		});
	}
}

function SaveDriverFinancedVirtualCardPopUp(model = "myModal") {
	if ($("#DriverFinancedCreditCardAdminPopupForm").valid()) {
		jQuery.blockUI({
			message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Just a moment...</h1>',
		});
        var oData = $("#DriverFinancedCreditCardAdminPopupForm").serialize();
        jQuery.post(SITE_URL + "admin/insurance/driver_financed_quotes/virtaulcard",oData,function (data) {
			$("#" + model).modal("hide");
		},'json').done(function () {
			jQuery.unblockUI();
		});
	}
}
function clearDriverFinancedVirtualCard(orderid,model = "myModal") {
	if (orderid.length>0) {
		jQuery.blockUI({
			message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Just a moment...</h1>',
		});
        jQuery.post(SITE_URL + "admin/insurance/driver_financed_quotes/deletevirtaulcard",{orderid:orderid},function (data) {
			$("#" + model).modal("hide");
		},'json').done(function () {
			jQuery.unblockUI();
		});
	}
}



// DIA fleet backup insurance popup

function OpenDiaFleeetBackupQuoteUploadPopUp(recordid, model = "myModal") {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery.post(SITE_URL + "admin/insurance/dia_fleet_backup_quotes/popup",{ recordid: recordid, model: model },function (data) {
			$("#" + model + " .modal-content").html(data);
			$("#" + model).modal("show").find(".modal-dialog").css("width", "850px");
	}).done(function () {
		jQuery.unblockUI();
		
		$("#DriverFinancedInsuranceQuoteDeclarationDoc").fileinput({
			browseLabel: "Browse",
			browseIcon: '<i class="icon-file-plus"></i>',
			uploadIcon: '<i class="icon-file-upload2"></i>',
			removeIcon: '<i class="icon-cross3"></i>',
			layoutTemplates: {
				icon: '<i class="icon-file-check"></i>',
			},
			uploadUrl: SITE_URL + "admin/insurance/dia_fleet_backup_quotes/saveImage", // server upload action
			uploadAsync: true,
			maxFileCount: 1,
			deleteUrl: SITE_URL + "admin/insurance/dia_fleet_backup_quotes/deleteImage",
			allowedFileExtensions: ["jpeg", "jpg", "png", "pdf"],
			overwriteInitial: false,
			maxFileSize: 10024,
			uploadExtraData: { id: recordid, type: "declaration_doc" },
			showCancel: false,
			showRemove: false,
		});
		$("#DriverFinancedInsuranceQuoteCard").fileinput({
			browseLabel: "Browse",
			browseIcon: '<i class="icon-file-plus"></i>',
			uploadIcon: '<i class="icon-file-upload2"></i>',
			removeIcon: '<i class="icon-cross3"></i>',
			layoutTemplates: {
				icon: '<i class="icon-file-check"></i>',
			},
			uploadUrl: SITE_URL + "admin/insurance/dia_fleet_backup_quotes/saveImage", // server upload action
			uploadAsync: true,
			maxFileCount: 1,
			deleteUrl: SITE_URL + "admin/insurance/dia_fleet_backup_quotes/deleteImage",
			allowedFileExtensions: ["jpeg", "jpg", "png", "pdf"],
			overwriteInitial: false,
			maxFileSize: 10024,
			uploadExtraData: { id: recordid, type: "insurance_card" },
			showCancel: false,
			showRemove: false,
		});
		
		$("#DriverFinancedCreditCardAdminPopupForm").validate();
		$('#DriverFinancedCreditCardCardNumber').mask("0000-0000-0000-0000",{placeholder: "Valid card number"});
		$('#DriverFinancedCreditCardExpDate').mask("00 / 00",{placeholder: "MM / YY"});
		$('#DriverFinancedCreditCardCvv').mask("0000",{placeholder: "CVC"});

		$(".date").datetimepicker({
			useCurrent: false, //Important! See issue #1075
			format: "YYYY-MM-DD",
		});
	});
	return false;
}

function SaveDiaFleetBackupQuoteUploadPopUp(model = "myModal",approve=false) {
	if ($("#DriverFinancedInsuranceQuoteAdminPopupForm").valid()) {
		jQuery.blockUI({
			message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Just a moment...</h1>',
		});
        var oData = $("#DriverFinancedInsuranceQuoteAdminPopupForm").serialize()+ "&approve="+ approve;
        jQuery.post(SITE_URL + "admin/insurance/dia_fleet_backup_quotes/save",oData,function (data) {
			$("#" + model).modal("hide");
		},'json').done(function () {
			jQuery.unblockUI();
		});
	}
}

function SaveDiaFleetBackupPolicyDetails(model = "myModal"){
	if ($("#PolicyDetailsAdminPopupForm").valid()) {
		jQuery.blockUI({
			message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Just a moment...</h1>',
		});
        var oData = $("#PolicyDetailsAdminPopupForm").serialize()+ "&policy=true";
        jQuery.post(SITE_URL + "admin/insurance/dia_fleet_backup_quotes/save",oData,function (data) {
			$("#" + model).modal("hide");
		},'json').done(function () {
			jQuery.unblockUI();
		});
	}
}