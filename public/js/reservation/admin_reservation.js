function getLicenseScanRequestPopup(booking) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/reservation/pickups/openLicenseScanRequestPopup",
		{ booking: booking },
		function (data) {
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "550px");
		}
	).done(function () {
		jQuery.unblockUI();
		$("form#openLicenseScanRequestPopup").validate();
	});
}

function pickUpUploadPhotoPopup(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/reservation/pickups/pickUpUploadPhoto",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "850px");
		}
	);
}
function updateVehicleDetails() {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Saving...</h1>',
		css: { "z-index": "9999" },
	});
	var data = new FormData($("#pickUpUploadPhoto").get(0));
	$.ajax({
		url: SITE_URL + "reservation/pickups/savePickUpUploadPhoto",
		type: "post",
		dataType: "JSON",
		data: data,
		processData: false,
		contentType: false,
		success: function (data, status) {
			if (!data.status) {
				alert(data.message);
				$("#myModal").modal("hide");
			} else {
				alert("Updated successfully");
			}
		},
		complete: function () {
			jQuery.unblockUI();
		},
	});
}



function loadPickupChecklistPopup(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Loading...</h1>',
	});
	jQuery
		.post(
			SITE_URL + "admin/reservation/pickups/loadstatuschecklist",
			{ orderid: orderid },
			function (data) {
				if (!data.status) {
					alert(data.message);
					return false;
				}
				$("#myModal .modal-content").html(data.html);
				$("#myModal").modal("show").find(".modal-dialog").css("width", "850px");
			},
			"json"
		)
		.done(function () {
			jQuery.unblockUI();
			$(".editable").editable({
				placement: "left",
				sourceOptions: "editable",
				source: [
					{ value: "No", text: "No" },
					{ value: "InProgress", text: "InProgress" },
					{ value: "Yes", text: "Yes" },
				],
				display: function (value, sourceData) {
					var colors = { No: "Red", InProgress: "#FF5722", Yes: "green" },
						elem = $.grep(sourceData, function (o) {
							return o.value == value;
						});

					if (elem.length) {
						$(this).text(elem[0].text).css("color", colors[value]);
					} else {
						$(this).empty();
					}
				},
				success: function (response, newValue) {
					if (response.status == "error") return response.msg;
				},
			});
			$(".editablenote").editable({
				placement: "left",
				success: function (response, newValue) {
					if (response.status == "error") return response.msg;
				},
			});
		});
	return false;
}