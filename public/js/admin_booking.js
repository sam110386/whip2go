function startBooking(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/startBooking",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
				$("#update_log table")
					.find("tr#tripRow" + data.orderid)
					.load(SITE_URL + "admin/bookings/load_single_row", {
						orderid: orderid,
					});
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}
function cancelBooking(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/loadcancelBooking",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show");
		}
	);
}
function processCancel(btn) {
	$(btn).prop("disabled", true);
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("#cancelForm").serialize();
	$.post(
		SITE_URL + "admin/bookings/cancelBooking",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				$("#myModal").modal("hide");
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
				$("#update_log table")
					.find("tr#tripRow" + data.orderid)
					.hide();
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}
function completeBooking(orderid, autorenew = 0) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/loadcompleteBooking",
		{ orderid: orderid, autorenew: autorenew },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "680px");
			if (!autorenew) {
				$("#myModal").modal("show");
			}
		}
	);
}
function processComplete(btn) {
	$(btn).prop("disabled", true);
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("#completeForm").serialize({ checkboxesAsBools: true });
	$.post(SITE_URL + "admin/bookings/completeBooking",params,function (data) {
			jQuery.unblockUI();
			if (data.status) {
				$("#myModal").modal("hide");
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
				$("#update_log table").find("tr#tripRow" + data.orderid).hide();
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}
function downloadBookingDoc(orderid, showupload = 0) {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/getinsurancepopup",
		{ orderid: orderid, showupload: showupload },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
		}
	);
}

function getinsurancedoc(orderid) {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(SITE_URL + "admin/bookings/getinsurancetoken",{ orderid: orderid },function (data) {
			jQuery.unblockUI();
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		}
	);
}
/**to get vehicle registration doc***/
function getVehicleRegistration(vehicleid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicles/getVehicleRegistration",
		{ vehicleid: vehicleid },
		function (data) {
			jQuery.unblockUI();
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		}
	);
}

function getmessagehistory(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/message_histories/loadmessagehistory",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show");
		}
	);
}
/*retry insurance fee payemnt*/
function retryinsurancefee(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> processing...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/retryinsurancefee",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			if (data.status == "success") {
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
				$("#update_log table")
					.find("tr#tripRow" + data.orderid)
					.load(SITE_URL + "admin/bookings/load_single_row", {
						orderid: orderid,
					});
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

/*retry Extra Mile Insurance fee payemnt*/
function retrydiainsurancefee(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> processing...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/retrydiainsurancefee",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			if (data.status == "success") {
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
				$("#update_log table")
					.find("tr#tripRow" + data.orderid)
					.load(SITE_URL + "admin/bookings/load_single_row", {
						orderid: orderid,
					});
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

function retryinitialfee(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> processing...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/retryinitialfee",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			if (data.status == "success") {
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
				$("#update_log table")
					.find("tr#tripRow" + data.orderid)
					.load(SITE_URL + "admin/bookings/load_single_row", {
						orderid: orderid,
					});
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}
/*retry rental fee payemnt*/
function retryrentalfee(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> processing...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/retryrentalfee",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			if (data.status == "success") {
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
				$("#update_log table")
					.find("tr#tripRow" + data.orderid)
					.load(SITE_URL + "admin/bookings/load_single_row", {
						orderid: orderid,
					});
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}
/*retry deposit fee payemnt*/
function retrydepositfee(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> processing...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/retrydepositfee",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			if (data.status == "success") {
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
				$("#update_log table")
					.find("tr#tripRow" + data.orderid)
					.load(SITE_URL + "admin/bookings/load_single_row", {
						orderid: orderid,
					});
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

/*retry toll fee payemnt*/
function retrytollfee(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> processing...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/retrytollfee",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			if (data.status == "success") {
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
				$("#update_log table")
					.find("tr#tripRow" + data.orderid)
					.load(SITE_URL + "admin/bookings/load_single_row", {
						orderid: orderid,
					});
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

/*retry EMF fee payemnt*/
function retryemf(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> processing...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/retryemf",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			if (data.status == "success") {
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
				$("#update_log table")
					.find("tr#tripRow" + data.orderid)
					.load(SITE_URL + "admin/bookings/load_single_row", {
						orderid: orderid,
					});
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}
/*retry Lateness fee payemnt*/
function retrylatefee(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> processing...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/retrylatefee",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			if (data.status == "success") {
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
				$("#update_log table")
					.find("tr#tripRow" + data.orderid)
					.load(SITE_URL + "admin/bookings/load_single_row", {
						orderid: orderid,
					});
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}
/*load booking transaction logs**/
function gettransactionlogs(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/payment_logs/index",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "850px");
		}
	);
}

/***non review page function **/
function getmessagehistory(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/message_histories/loadmessagehistory",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "800px");
		}
	);
}
function getreviewpopup(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/booking_reviews/reviewpopup",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "450px");
		}
	);
}

/******send new message***/
/**send new message**/
function loadnewmessgae(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/message_histories/loadnewmessage",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "800px");
		}
	);
}

/****send message**/
function SendNewMessage() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	var data = jQuery("form#newmessageform").serialize();
	$.post(
		SITE_URL + "admin/message_histories/sendnewmessage",
		data,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				alert(data.message);
				$("#myModal").modal("hide");
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

/****report page related functions***/
function openTripDetails(tripId, thisObj) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery.post(
		SITE_URL + "admin/reports/details/" + tripId,
		{},
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "1050px");
		}
	);
	jQuery.unblockUI();
	return false;
}
//open combined booking details
function openCombinedBookingDetails(tripId) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery.post(
		SITE_URL + "admin/reports/autorenewddetails/" + tripId,
		{},
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "1050px");
		}
	);
	jQuery.unblockUI();
	return false;
}
function reviewimages(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery.post(
		SITE_URL + "admin/booking_reviews/reviewimages/" + orderid,
		{},
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show");
		}
	);
	jQuery.unblockUI();
	return false;
}
function loadsubbooking(orderid) {
	var havingchild = jQuery("tr#tr_" + orderid).attr("rel-parent");
	if (havingchild == "yes") {
		jQuery("tbody tr.child_" + orderid).each(function () {
			jQuery(this).remove();
		});
		jQuery("tr#tr_" + orderid).attr("rel-parent", "no");
		return false;
	}
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery.post(
		SITE_URL + "admin/reports/loadsubbooking/" + orderid,
		{},
		function (data) {
			if (data.status == "success") {
				jQuery("tr#tr_" + data.booking_id).after(data.data);
				jQuery("tr#tr_" + data.booking_id).attr("rel-parent", "yes");
			}
			jQuery.unblockUI();
		},
		"json"
	);

	return false;
}

/**vehicle listing page functions***/
function loadVehicleStatus(vehicleid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicles/loadVehicleStatus",
		{ vehicleid: vehicleid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
		}
	);
}
/**save status***/
function changeVehicleStatus() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("#statusChangeForm").serialize();
	$.post(
		SITE_URL + "admin/vehicles/changeVehicleStatus",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				$("#myModal").modal("hide");
				$("table.vehiclelist")
					.find("tr#" + data.vehicleid)
					.load(SITE_URL + "admin/vehicles/loadSingleRow", {
						vehicleid: data.vehicleid,
					});
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}
/**for agreement PDF **/
function getagreement(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/getagreement",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		}
	);
}

/**for SMS logs listing page **/
function messageDetail(id) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery.post(SITE_URL + "admin/smslogs/details/" + id, {}, function (data) {
		jQuery.unblockUI();
		jQuery.colorbox({
			width: "700px;",
			html: data,
		});
	});
	jQuery.unblockUI();
	return false;
}

/**for SMS logs listing page **/
function deleteMessage(id) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/smslogs/delete/" + id,
		{},
		function (data) {
			jQuery.unblockUI();
			if (data.status == "success") {
				jQuery(".right_content table #tr_" + data.recordid).remove();
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

/**vehicle passtime status update***/
function changePasstimeVehicleStatus(vehicleid, status) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicles/changePasstimeVehicleStatus",
		{ vehicleid: vehicleid, status: status },
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				$("#myModal").modal("hide");
				$("table.vehiclelist")
					.find("tr#" + data.vehicleid)
					.load(SITE_URL + "admin/vehicles/loadSingleRow", {
						vehicleid: data.vehicleid,
					});
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

/**for Change attached CC info save**/
function changeVehicleLockTime(booking) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/loadvehicleexpiretime",
		{ booking: booking },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "450px");
			$("#TextPasstimeThreshold").datetimepicker({});
		}
	);
}
/**process Change attached CC info save**/
function processVehicleLockTime() {
	if (!$("#loadvehicleexpiretime").valid()) {
		return false;
	}
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("#loadvehicleexpiretime").serialize();
	$.post(
		SITE_URL + "admin/bookings/processvehicleexpiretime",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				$("#myModal").modal("hide");
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

function checkrApprove(orderid) {
	swal(
		{
			title: "",
			text: "Are you sure you want to approve this booking?",
			type: "warning",
			showCancelButton: true,
			confirmButtonClass: "btn-success",
			confirmButtonText: "Yes",
			cancelButtonText: "No",
			closeOnConfirm: true,
			closeOnCancel: true,
		},
		function (isConfirm) {
			if (isConfirm) {
				//create related booking
				jQuery.blockUI({
					message:
						'<h1><img src="' +
						SITE_URL +
						'img/select2-spinner.gif" /> loading...</h1>',
					css: { "z-index": "9999" },
				});
				$.post(
					SITE_URL + "admin/bookings/checkrapprove",
					{ orderid: orderid },
					function (data) {
						jQuery.unblockUI();
						if (data.status) {
							$("#update_log table")
								.find("tr#tripRow" + data.orderid)
								.load(SITE_URL + "admin/bookings/load_single_row", {
									orderid: orderid,
								});
						} else {
							swal("Error!", data.message, "error");
						}
					},
					"json"
				);
			}
		}
	);
}
function checkrDisapprove(orderid) {
	swal(
		{
			title: "",
			text: "Are you sure you want to disapprove this booking?",
			type: "warning",
			showCancelButton: true,
			confirmButtonClass: "btn-success",
			confirmButtonText: "Yes",
			cancelButtonText: "No",
			closeOnConfirm: true,
			closeOnCancel: true,
		},
		function (isConfirm) {
			if (isConfirm) {
				//create related booking
				jQuery.blockUI({
					message:
						'<h1><img src="' +
						SITE_URL +
						'img/select2-spinner.gif" /> loading...</h1>',
					css: { "z-index": "9999" },
				});
				$.post(
					SITE_URL + "admin/bookings/checkrdisapprove",
					{ orderid: orderid },
					function (data) {
						jQuery.unblockUI();
						if (data.status) {
							$("#update_log table")
								.find("tr#tripRow" + data.orderid)
								.remove();
						} else {
							swal("Error!", data.message, "error");
						}
					},
					"json"
				);
			}
		}
	);
}

/***MVR reports page related functions***/
function getReport(reportid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/mvr_reports/report",
		{ reportid: reportid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "450px");
		}
	);
}

/***MVR reports page related functions***/
function getVehicleReport(reportid,mymodal='myModal') {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/mvr_reports/vehiclereport",
		{ reportid: reportid },
		function (data) {
			jQuery.unblockUI();
			$("#"+mymodal+" .modal-content").html(data);
			$("#"+mymodal).modal("show").find(".modal-dialog").css("width", "650px");
		}
	);
}
/***MVR reports page get Active Booking***/
function getActiveBooking(userid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/mvr_reports/loadactivebooking",
		{ userid: userid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
		}
	);
}
/****MVR cancel booking*/
function cancelBookingMvr(bookingid, btn) {
	$(btn).prop("disabled", true);
	if (!confirm("Are you sure you want to cancel this booking?")) {
		$(btn).prop("disabled", false);
		return false;
	}
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/mvr_reports/cancelMvrBooking",
		{ bookingid: bookingid },
		function (data) {
			jQuery.unblockUI();
			if (data.status === "success") {
				//$("#myModal").modal('hide');
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
				$("#mvrtable")
					.find("tr#tripRow" + data.orderid)
					.hide();
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

/****MVR cancel booking*/
function cancelMvrResevationBooking(bookingid, btn) {
	$(btn).prop("disabled", true);
	if (!confirm("Are you sure you want to cancel this pending booking?")) {
		$(btn).prop("disabled", false);
		return false;
	}
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/mvr_reports/cancelMvrResevationBooking",
		{ bookingid: bookingid },
		function (data) {
			jQuery.unblockUI();
			if (data.status === "success") {
				//$("#myModal").modal('hide');
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
				$("#mvrtable")
					.find("tr#tripRow" + data.orderid)
					.hide();
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

/****Update booking Payment Setting*/
function updateOrderDepositRules(bookingid) {
	if (
		!confirm(
			"Are you sure you want to update payment setting for this booking?"
		)
	) {
		return false;
	}
	location.href = SITE_URL + "admin/order_deposit_rules/update/" + bookingid;
}

/**Admin Manage User listing page**/
/***MVR reports page get Active Booking***/
function showUberLyft(userid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/users/showargyldetails",
		{ userid: userid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
		}
	);
}

/***function to show pickup/delivery data****/
function pickupDelivery(data, tripid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	jQuery.unblockUI();

	var jsondata = atob(data);
	var strfinal =
		'<div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button></div>' +
		'<div class="modal-body">' +
		'<legend class="text-semibold">Data could not be parsed</legend>' +
		"</div>" +
		'<div class="modal-footer">' +
		'<button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>' +
		"</div>";
	if (jsondata.length > 0) {
		var JSN = JSON.parse(jsondata);
		strfinal =
			'<div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button></div>' +
			'<div class="modal-body">' +
			'<form class="form-horizontal">' +
			"<fieldset>" +
			'<legend class="text-semibold">Delivery Information</legend>';
		if (JSN["pickup"]) {
			strfinal +=
				'<div class="form-group">' +
				'<label class="col-lg-4 control-label">PICKUP:</label>' +
				'<div class="col-lg-8">YES</div>' +
				"</div>";
		} else {
			strfinal +=
				'<div class="form-group">' +
				'<label class="col-lg-4 control-label">DELIVERY:</label>' +
				'<div class="col-lg-8">YES</div>' +
				"</div>" +
				'<div class="form-group">' +
				'<label class="col-lg-4 control-label">ADDRESS:</label>' +
				'<div class="col-lg-8">' +
				JSN["address"] +
				"</div>" +
				"</div>" +
				'<div class="form-group">' +
				'<label class="col-lg-4 control-label">PHONE:</label>' +
				'<div class="col-lg-8">' +
				JSN["phone"] +
				"</div>" +
				"</div>" +
				'<div class="form-group">' +
				'<label class="col-lg-4 control-label">DATETIME:</label>' +
				'<div class="col-lg-8">' +
				JSN["datetime"] +
				"</div>" +
				"</div>";
		}
		strfinal += "</fieldset>";
		strfinal +=
			'<legend class="text-semibold">Uber Booking Information</legend>' +
			'<fieldset id="uberbookingwrapper"></fieldset>' +
			"</form>" +
			"</form>" +
			"</div>" +
			'<div class="modal-footer">' +
			'<button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>' +
			"</div>";
	}
	$("#myModal .modal-content").html(strfinal);
	$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
	if (jsondata.length > 0) {
		//load uber booking data
		loadUberBooking(tripid);
	}
}
function loadUberBooking(tripid) {
	var ele = $("#myModal #uberbookingwrapper");
	ele.block({
		message: '<i class="icon-spinner4 spinner"></i>',
		overlayCSS: {
			backgroundColor: "#fff",
			opacity: 0.8,
			cursor: "wait",
		},
		css: { border: 0, padding: 0, backgroundColor: "transparent" },
	});
	$.post(
		SITE_URL + "admin/uber/index/getbooking",
		{ orderid: tripid },
		function (data) {
			ele.html(data.booking);
		},
		"json"
	).done(function () {
		ele.unblock();
	});
}

function cancelReservation(lease_id) {
	swal(
		{
			title: "",
			text: "Are you sure you want to cancel this?",
			type: "warning",
			input: "text",
			showCancelButton: true,
			confirmButtonClass: "btn-success",
			confirmButtonText: "Yes",
			cancelButtonText: "No",
			closeOnConfirm: true,
			closeOnCancel: true,
		},
		function (isConfirm) {
			if (isConfirm) {
				//create related booking
				jQuery.blockUI({
					message:
						'<h1><img src="' +
						SITE_URL +
						'img/select2-spinner.gif" /> loading...</h1>',
					css: { "z-index": "9999" },
				});
				$.post(
					SITE_URL + "admin/vehicle_reservations/loadcancelblock",
					{ lease_id: lease_id },
					function (data) {
						jQuery.unblockUI();
						$("#myModal .modal-content").html(data).show();
						$("#myModal").modal("show");
					}
				);
			}
		}
	);
}
function processCancelReservation() {
	//create related booking
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("form#cancelReservationForm").serialize();
	$.post(
		SITE_URL + "admin/vehicle_reservations/markBookingCancel",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				jQuery(
					"#postsPaging table tbody tr#tripRow" + data.result.lease_id
				).remove();
				$("#myModal").modal("hide");
			} else {
				swal("Error!", data.message, "error");
			}
		},
		"json"
	);
}

function createVehicleReservation(lease_id) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/createBooking",
		{ lease_id: lease_id },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data).show();
			$("#myModal").modal("show").find(".modal-dialog").css("width", "1100px");
			initializeDate();
		}
	);
}
function initializeDate() {
	$("#daterangefrom").datetimepicker({ format: "MM/DD/YYYY" });
	$("#daterangeto").datetimepicker({
		useCurrent: false, //Important! See issue #1075
		format: "MM/DD/YYYY",
	});
	$("#daterangefrom").on("dp.change", function (e) {
		$("#daterangeto").data("DateTimePicker").minDate(e.date);
	});
	$("#daterangeto").on("dp.change", function (e) {
		$("#daterangefrom").data("DateTimePicker").maxDate(e.date);
	});
}
function SaveBooking() {
	var pickup_time = jQuery("#TextPickupTime").val();
	var lease_id = jQuery("#TextLeaseId").val();
	var errMsg = "";
	if (pickup_time == "") {
		errMsg += "Please enter Pickup Time\n";
	}
	if (errMsg !== "") {
		alert(errMsg);
		return false;
	}
	var params = $("#triplogForm").serialize();
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	jQuery.post(
		SITE_URL + "admin/vehicle_reservations/saveVehicleBooking",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
				$("#myModal").modal("hide");
				$.post(
					SITE_URL + "admin/vehicle_reservations/markBookingCompleted",
					{ lease_id: lease_id },
					function (data) {
						jQuery(
							"#postsPaging table tbody tr#tripRow" + data.lease_id
						).remove();
					}
				);
			} else {
				swal("Error!", data.message, "error");
			}
		},
		"json"
	);
	return false;
}

function Updateenddatetime(tripId) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/transactions/updateenddatetime",
		{ booking_id: tripId },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content .modal-body").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");

			$("#CsOrderEndTiming").datetimepicker({
				useCurrent: false, //Important! See issue #1075
				//format: 'MM/DD/YYYY h:i A'
			});
		}
	);
}

/**process Change attached CC info save**/
function changeEndTiming() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("#endTimeChangeForm").serialize();
	$.post(
		SITE_URL + "admin/transactions/changeendtiming",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				$("#myModal").modal("hide");
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

/*****Reopen booking from Booking Review waiting page****/
function reopenbooking(orderid) {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(SITE_URL + "admin/booking_reviews/reopenbookingpopup",{ orderid: orderid },
		function (data) {
			jQuery("#plaidModal .modal-content").html(data);
			jQuery("#plaidModal").modal("show");
		}).done(function(){
		jQuery.unblockUI();
	});
}
function ReopenBooking(){
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	var params=$("#BookingReviewReopenForm").serialize();
	$.post(SITE_URL + "admin/booking_reviews/reopenbooking",params,
		function (data) {
			if (data.status) {
				$("table.table-responsive tbody tr#tripRow" + data.orderid).hide();
				swal({
					title: data.message,
					text: "I will close in 2 seconds.",
					confirmButtonColor: "#2196F3",
					timer: 2000,
				});
			} else {
				alert(data.message);
			}
		},
	"json").done(function(){
		jQuery.unblockUI();
		jQuery("#plaidModal").modal("hide");
	});
}
/**Update Vehicle GPS info**/
function changeVehicleGps(booking) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/loadvehiclegps",
		{ booking: booking },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "550px");
		}
	);
}
/**process Vehicle GPS details save**/
function processVehicleGps(sync = false) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("#loadvehiclegps").serialize() + "&sync=" + sync;
	$.post(
		SITE_URL + "admin/bookings/updatevehiclegps",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				if (sync) {
					$("#TextPasstimeSerialno").val(data.result);
				}
				alert(data.message);
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

function disableVehicleTemp() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = {
		booking: $("#loadvehiclegps #TextBooking").val(),
		vehicle_id: $("#loadvehiclegps #TextVehicleId").val(),
		status: $("#loadvehiclegps #VehicleDisableTemp").is(":checked") ? 1 : 0,
	};
	$.post(
		SITE_URL + "admin/bookings/diabletempvehicle",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				alert(data.message);
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

/***function to get user details, on pending trip listing page***/
function getuserdetails(userid, owner, booking = "") {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/getuserdetails",
		{ userid: userid, owner: owner, booking: booking },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "550px");
			//initialize editable element
			$("#provenIncome,#statedIncome").editable({
				success: function (response, newValue) {
					if (response.status == "error") return response.msg;
				},
			});
		}
	);
}
/***function to get user bank statement, on pending trip listing page***/
function getplaidrecord(userid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/getplaidrecord",
		{ userid: userid },
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				$("#plaidModal .modal-content")
					.attr("id", "bankdetail")
					.html(data.view);
				$("#plaidModal")
					.modal("show")
					.find(".modal-dialog")
					.css("width", "650px");
				plaidtoken = data.plaidtoken;
				userid = data.userid;
				loadbankbalance(plaidtoken, userid);
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

$("#plaidModal").on("show.bs.modal", function () {
	var modalParent = $(this).attr("data-modal-parent");
	$(modalParent).css("opacity", 0);
});

$("#plaidModal").on("hidden.bs.modal", function () {
	var modalParent = $(this).attr("data-modal-parent");
	$(modalParent).css("opacity", 1);
});

$("#statementModal").on("show.bs.modal", function () {
	var modalParent = $(this).attr("data-modal-parent");
	$(modalParent).css("opacity", 0);
});

$("#statementModal").on("hidden.bs.modal", function () {
	var modalParent = $(this).attr("data-modal-parent");
	$(modalParent).css("opacity", 1);
});

function loadbankbalance(plaidtoken, userid) {
	$("#bankdetail .plaidbalance").each(function (index) {
		var ele = $(this);
		showUIBlocker(ele.parent());
		console.log(index + ": " + ele.attr("rel-token"));
		var acccountid = ele.attr("rel-token");
		$.post(
			SITE_URL + "admin/vehicle_reservations/getplaidbalance",
			{ userid: userid, plaid_token: plaidtoken, acccountid: acccountid },
			function (resp) {
				ele.html(resp.balance);
				console.log("balance: " + resp.balance);
			},
			"json"
		).done(function () {
			$(ele.parent()).unblock();
		});
	});
}
function showUIBlocker(ele) {
	$(ele).block({
		message: '<i class="icon-spinner4 spinner"></i>',
		overlayCSS: {
			backgroundColor: "#fff",
			opacity: 0.8,
			cursor: "wait",
		},
		css: { border: 0, padding: 0, backgroundColor: "transparent" },
	});
}
function loadbankstatement(plaidtoken, userid, acccountid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/bankstatement",
		{ userid: userid, plaid_token: plaidtoken, acccountid: acccountid },
		function (resp) {
			if (resp.status) {
				$("#statementModal .modal-content").html(resp.transactions);
				$("#statementModal")
					.modal("show")
					.find(".modal-dialog")
					.css("width", "650px");
			} else {
				alert(resp.message);
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

function CheckOdometer(vehicleid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/checkodometer",
		{ vehicleid: vehicleid },
		function (resp) {
			if (resp.status === "success") {
				$("#gpstester .messagedisplay").html(resp.html);
			} else {
				alert(resp.message);
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

function CheckStaterInterrupt(vehicleid, orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/checkStarterInterrupt",
		{ vehicleid: vehicleid, orderid: orderid },
		function (resp) {
			if (resp.status === "success") {
				$("#gpstester .messagedisplay").html(resp.html);
			} else {
				alert(resp.message);
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

function DisableStaterInterrupt(vehicleid, orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/disableStaterInterrupt",
		{ vehicleid: vehicleid, orderid: orderid, disable: 1 },
		function (resp) {
			if (resp.status) {
				$("#starterWorkswrapper .starterWorkOptions").show();
				$("#starterWorkswrapper .starterWorks").hide();
			} else {
				alert(resp.message);
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

function StaterInterruptWorks(vehicleid, orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/staterInterruptWorks",
		{ vehicleid: vehicleid, orderid: orderid },
		function (resp) {
			if (resp.status === "success") {
				// $("#gpstester .messagedisplay").html("Great. Please try to Enable the car");
				$("#starterWorkswrapper .starterWorks").show();
				$("#starterWorkswrapper .starterWorkOptions").hide();
			} else {
				alert(resp.message);
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

function EnableStaterInterrupt(vehicleid, orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/disableStaterInterrupt",
		{ vehicleid: vehicleid, orderid: orderid, disable: 0 },
		function (resp) {
			if (resp.status) {
				$("#starterWorkswrapper .starterEnableWorkOptions").show();
			} else {
				alert(resp.message);
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

/***function to get Vehicle details, on pending trip listing page***/
function getvehicledetails(vehicleid, orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicles/getvehicledetails",
		{ vehicleid: vehicleid, orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "1150px");
		}
	);
}

/***function to get Vehicle details, on pending trip listing page***/
function updateVehicleDetails() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	//var fromdata=$("#updateVehicleDetails").serialize();
	var data = new FormData($("#updateVehicleDetails").get(0));
	$.ajax({
		url: SITE_URL + "admin/vehicles/updateVehicleDetails",
		type: "post",
		dataType: "JSON",
		data: data,
		processData: false,
		contentType: false,
		success: function (data, status) {
			if (!data.status) {
				alert(data.message);
			} else {
				alert("Updated successfully");
			}
		},
		complete: function () {
			jQuery.unblockUI();
		},
	});
}

/***function to Change the Vehicle, on pending trip listing page***/
function changeReservationVehicle(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/changeVehicle",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "550px");
		}
	);
}

function updateReservationVehicle() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var fromdata = $("#updateVehicleDetails").serialize();
	$.post(
		SITE_URL + "admin/vehicle_reservations/updateReservationVehicle",
		fromdata,
		function (data) {
			if (!data.status) {
				alert(data.message);
			} else {
				alert("Updated successfully");
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

/***function to Change the Vehicle, on pending trip listing page***/
function changeDatetime(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/changeDatetime",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "550px");
			initializeDate();
		}
	);
}

function updateDatetime() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var fromdata = $("#updateVehicleDetails").serialize();
	$.post(
		SITE_URL + "admin/vehicle_reservations/updateDatetime",
		fromdata,
		function (data) {
			if (!data.status) {
				alert(data.message);
			} else {
				alert(data.message);
				$("#myModal").modal("hide");
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

function getVehicleGps(vehicleid, type) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicles/getVehicleGps",
		{ vehicleid: vehicleid, type: type },
		function (data) {
			if (!data.status) {
				alert(data.message);
			} else {
				var $elem = $("#updateVehicleDetails #" + type);
				$elem.editable("setValue", data.gps_serialno).editable("toggle");
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

/***function to Change the Vehicle, on pending trip listing page***/
function changeReservationStatus(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/changeStatus",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "550px");
		}
	);
}

//save Reservation status change Request
function updateReservationStatus() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var fromdata = $("#updateVehicleStatus").serialize();
	$.post(
		SITE_URL + "admin/vehicle_reservations/changeSaveStatus",
		fromdata,
		function (data) {
			if (!data.status) {
				alert(data.message);
			} else {
				alert(data.message);
				$("#myModal").modal("hide");
				$("#postsPaging table")
					.find("tr#tripRow" + data.orderid)
					.load(SITE_URL + "admin/vehicle_reservations/singleload", {
						orderid: data.orderid,
					});
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

/***function to get the Vehicle pending status log, on pending trip listing page***/
function vehicleReservationLog(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/vehicleReservationLog",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			if (!data.status) {
				alert(data.message);
				return false;
			}
			$("#myModal .modal-content").html(data.view);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "550px");
		},
		"json"
	);
}

function calculateFareMatrix() {
	if (!$("#VehicleOfferForm").valid({ ignore: ":hidden" })) return;
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var formdata = $("#VehicleOfferForm").serialize();
	$.post(
		SITE_URL + "admin/bookings/getVehicleDynamicFareMatrix",
		formdata,
		function (obj) {
			jQuery.unblockUI();

			$("#VehicleOfferDays").val(obj.days);
			$("#VehicleOfferDayRent")
				.val(obj.dayRent)
				.removeClass("required")
				.addClass("required");
			$("#VehicleOfferInsurance").val(obj.dayInsurance);
			$("#VehicleOfferEmf").val(obj.emf);
			$("#VehicleOfferProgramFee").val(obj.program_fee);
			$("#VehicleOfferTotalInsurance").val(obj.total_insurance);
			$("#VehicleOfferTotalProgramCost").val(obj.total_program_cost);
			$("#VehicleOfferJson").val(JSON.stringify(obj));
			//adjust duration to available max days

			var program = "";
			program += "<ul>";
			program +=
				"<li><strong>Adjusted Program Length:</strong> " +
				obj.days +
				"(days)</li>";
			program +=
				"<li><strong>Total Program Cost:</strong> " +
				obj.total_program_cost +
				"</li>";
			program +=
				"<li><strong>Program Fee:</strong> " + obj.program_fee + "</li>";
			program +=
				"<li><strong>Insurance Cost To Driver:</strong> " +
				obj.total_insurance +
				"</li>";
			program +=
				"<li><strong>Day Insurance:</strong> " + obj.dayInsurance + "</li>";
			program += "<li><strong>Day Rent:</strong> " + obj.dayRent + "</li>";
			program += "<li><strong>EMF Per Day:</strong> " + obj.emf + "</li>";
			program +=
				"<li><strong>Day Rent With Emf:</strong> " + obj.dayEmfRent + "</li>";
			program +=
				"<li><strong>Monthly Miles:</strong> " + obj.month_miles + "</li>";
			program += "<li><strong>Monthly EMF:</strong> " + obj.month_emf + "</li>";
			program += "<li><strong>Weekly Rent:</strong> " + obj.weekRent + "</li>";
			program +=
				"<li><strong>Weekly EMF Rent:</strong> " +
				obj.weekkEmfRent +
				"</li></ul>";
			$("#calculations").html(program);
			$("#VehicleOfferForm button#saveGoalRecalculation").prop(
				"disabled",
				false
			);
		},
		"json"
	);
}

function saveRecalculation() {
	if (!$("#VehicleOfferForm").valid({ ignore: ":hidden" })) return;
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var formdata = $("#VehicleOfferForm").serialize();
	$.post(
		SITE_URL + "admin/bookings/saveGoalRecalculation",
		formdata,
		function (obj) {
			jQuery.unblockUI();
			if (!obj.status) {
				alert(obj.message);
			} else {
				alert(obj.message);
				goBack("/admin/bookings/index");
			}
		},
		"json"
	);
	return false;
}

function openBookingDetails(order) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery.post(
		SITE_URL + "admin/report/pastdues/details",
		{ order: order },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "1050px");
		}
	);
	jQuery.unblockUI();
	return false;
}

$(document).ready(function () {
	$("#VehicleOfferGoal").change(function () {
		if ($(this).val() == "") return;

		if ($(this).val() === "custom") {
			$("#VehicleOfferDownpayment").attr("readonly", false);
		} else {
			var down = $("#VehicleOfferTotalcost").val() * ($(this).val() / 100);
			$("#VehicleOfferDownpayment").val(parseFloat(down).toFixed(2));
			$("#VehicleOfferDownpayment").attr("readonly", true);
		}
	});
});

/**vehicle listing page functions***/
function loadextendtime(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/loadextendtime",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
			$("#TextExtend").datetimepicker({});
		}
	);
}
/**save status***/
function changeExtendTime() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("#loadextendtime").serialize();
	$.post(
		SITE_URL + "admin/bookings/changeExtendTime",
		params,
		function (data) {
			if (data.status) {
				$("#myModal").modal("hide");
			} else {
				alert(data.message);
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

function captureVehicleReservationPayment(lease_id) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/capturepayment",
		{ lease_id: lease_id },
		function (data) {
			$("#myModal .modal-content").html(data).show();
			$("#myModal").modal("show").find(".modal-dialog").css("width", "1100px");
		}
	).done(function () {
		jQuery.unblockUI();
	});
}

function authorizePaymentVehicleReservation(amt, orderid, type, opt = null) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/processcapturepayment",
		{ orderid: orderid, amt: amt, type: type, opt: opt },
		function (data) {
			if (data.status != "success") {
				alert(data.message);
				return false;
			}
			$("#myModal").modal("hide");
		}
	).done(function () {
		jQuery.unblockUI();
	});
}
function capturePaymentVehicleReservation(paymentid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/paymentcapturevehiclereservation",
		{ paymentid: paymentid },
		function (data) {
			if (data.status != "success") {
				swal(
					{
						title: "Charge again ?",
						text: data.message,
						icon: "warning",
						showCancelButton: true,
						confirmButtonColor: "#3085d6",
						cancelButtonColor: "#d33",
						confirmButtonText: "Yes, Charge Again",
					},
					function (isConfirm) {
						if (isConfirm) {
							$.post(
								SITE_URL +
									"admin/vehicle_reservations/recapturevehiclereservation",
								{ paymentid: paymentid },
								function (data) {
									if (data.status != "success") {
										alert(data.message);
									} else {
										alert(data.message);
										$("#myModal").modal("hide");
									}
								}
							);
						} else {
							alert("canceled");
						}
					}
				);
				return false;
			}
			$("#myModal").modal("hide");
		}
	).done(function () {
		jQuery.unblockUI();
	});
}

/*retry initial fee payemnt*/
function partialPayment(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/partial_payment",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "850px");
			///
			$(".panelpanelwhite [data-action=collapse]").click(function (e) {
				e.preventDefault();
				var $panelCollapse = $(this)
					.parent()
					.parent()
					.parent()
					.parent()
					.nextAll();
				$(this).parents(".panelpanelwhite").toggleClass("panel-collapsed");
				$(this).toggleClass("rotate-180");

				//containerHeight(); // recalculate page height

				$panelCollapse.slideToggle(150);
			});
			$("#BookingDate").datetimepicker({
				useCurrent: false, //Important! See issue #1075
				format: "MM/DD/YYYY",
				minDate: $("#BookingDate").attr("rel-startdate"),
				maxDate: $("#BookingDate").attr("rel-enddate"),
			});
			$(".paymenttype").change(function () {
				if ($(this).val() == "fullpay") {
					$("#fullpay").removeClass("hide").addClass("show");
					$("#partial").removeClass("show").addClass("hide");
					$("#advance").removeClass("show").addClass("hide");
				} else if ($(this).val() == "partial") {
					$("#partial").removeClass("hide").addClass("show");
					$("#fullpay").removeClass("show").addClass("hide");
					$("#advance").removeClass("show").addClass("hide");
				} else if ($(this).val() == "advance") {
					$("#partial").removeClass("show").addClass("hide");
					$("#fullpay").removeClass("show").addClass("hide");
					$("#advance").removeClass("hide").addClass("show");
				}
			});
		}
	);
}
function processPaymentRetry() {
	if (!$("#paymentretry").valid()) {
		return false;
	}
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var fromdata = $("#paymentretry").serialize();
	$.post(
		SITE_URL + "admin/bookings/process_partial_payment",
		fromdata,
		function (data) {
			alert(data.message);
			if (data.status) {
				$("#myModal").modal("hide");
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

//Open Driver Transactions popup
function openUserTransactions(userid, bookingid, currency) {
	$.post(
		SITE_URL + "admin/transactions/usertransactions/" + userid,
		{ bookingid: bookingid, currency: currency },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "90%");
		}
	).done(function () {
		$("#ReportDriverTransactionAdminUsertransactionsForm #SearchTime").change(
			function () {
				jQuery.blockUI({
					message:
						'<h1><img src="' +
						SITE_URL +
						'img/select2-spinner.gif" /> Loading...</h1>',
					css: { "z-index": "9999" },
				});
				var time = $(this).val();
				var user = $(
					"#ReportDriverTransactionAdminUsertransactionsForm #SearchUserId"
				).val();
				$.post(
					SITE_URL +
						"admin/transactions/usertransactions/" +
						user +
						"/" +
						time +
						"/1",
					{ bookingid: bookingid, currency: currency },
					function (data) {
						jQuery.unblockUI();
						$("#myModal .modal-content #transsactionlisting").html(data);
					}
				);
			}
		);
	});
}

function getEvBattery() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("#loadvehiclegps").serialize();
	$.post(
		SITE_URL + "admin/smart_car/smart_cars/getbattery",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				$("#myModal span.batteryspan").html(data.battery);
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}
function geotabkeylessLock() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("#loadvehiclegps").serialize();
	$.post(
		SITE_URL + "admin/bookings/geotabkeylesslock",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				alert("Request processed successfully");
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}
function geotabkeylessUnLock() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("#loadvehiclegps").serialize();
	$.post(
		SITE_URL + "admin/bookings/geotabkeylessunlock",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				alert("Request processed successfully");
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

/**for agreement PDF **/
function getReservationInsuranceDoc(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/insudoc",
		{ id: orderid },
		function (data) {
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		},
		"json"
	).done(function () {});
}

function chargePartialAmtPopup(userid, booking = "", currency = "") {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/wallet/chargepartialamtpopup",
		{ userid: userid, bookingid: booking, currency: currency },
		function (data) {
			jQuery.unblockUI();
			$("#plaidModal .modal-content").html(data);
			$("#plaidModal")
				.modal("show")
				.find(".modal-dialog")
				.css("width", "650px");
		}
	).done(function () {
		jQuery.unblockUI();
		$("#chargepartialamtpopup").validate();
	});
}

function walletChargePartialAmt() {
	if (!$("#chargepartialamtpopup").valid()) {
		return false;
	}
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Charging...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("#chargepartialamtpopup").serialize();
	$.post(
		SITE_URL + "admin/wallet/chargepartialamt",
		params,
		function (data) {
			if (data.status) {
				alert("Request processed successfully");
				$("#plaidModal").modal("hide");
			} else {
				alert(data.message);
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

function OpenInsurancePayerUploadPopUp(order, model = "myModal") {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery
		.post(
			SITE_URL + "admin/insurance/payers/popup",
			{ order: order, model: model },
			function (data) {
				jQuery.unblockUI();
				$("#" + model + " .modal-content").html(data);
				$("#" + model)
					.modal("show")
					.find(".modal-dialog")
					.css("width", "850px");
			}
		)
		.done(function () {
			$("#InsurancePayerDeclarationDoc").fileinput({
				browseLabel: "Browse",
				browseIcon: '<i class="icon-file-plus"></i>',
				uploadIcon: '<i class="icon-file-upload2"></i>',
				removeIcon: '<i class="icon-cross3"></i>',
				layoutTemplates: {
					icon: '<i class="icon-file-check"></i>',
				},
				uploadUrl: SITE_URL + "admin/insurance/payers/saveImage", // server upload action
				uploadAsync: true,
				maxFileCount: 1,
				deleteUrl: SITE_URL + "admin/insurance/payers/deleteImage",
				allowedFileExtensions: ["jpeg", "jpg", "png", "pdf"],
				overwriteInitial: false,
				maxFileSize: 10024,
				uploadExtraData: { id: order, type: "declaration_doc" },
				showCancel: false,
				showRemove: false,
			});
			$("#InsurancePayerInsuranceCard").fileinput({
				browseLabel: "Browse",
				browseIcon: '<i class="icon-file-plus"></i>',
				uploadIcon: '<i class="icon-file-upload2"></i>',
				removeIcon: '<i class="icon-cross3"></i>',
				layoutTemplates: {
					icon: '<i class="icon-file-check"></i>',
				},
				uploadUrl: SITE_URL + "admin/insurance/payers/saveImage", // server upload action
				uploadAsync: true,
				maxFileCount: 1,
				deleteUrl: SITE_URL + "admin/insurance/payers/deleteImage",
				allowedFileExtensions: ["jpeg", "jpg", "png", "pdf"],
				overwriteInitial: false,
				maxFileSize: 10024,
				uploadExtraData: { id: order, type: "insurance_card" },
				showCancel: false,
				showRemove: false,
			});
			$(".date").datetimepicker({
				useCurrent: false, //Important! See issue #1075
				format: "YYYY-MM-DD",
			});
		});
	jQuery.unblockUI();
	return false;
}

function SaveInsurancePayerUploadPopUp(model = "myModal") {
	if ($("#InsurancePayerAdminPopupForm").valid()) {
		jQuery.blockUI({
			message:
				'<h1><img src="' +
				SITE_URL +
				'img/select2-spinner.gif" /> Just a moment...</h1>',
		});
		jQuery
			.post(
				SITE_URL + "admin/insurance/payers/save",
				$("#InsurancePayerAdminPopupForm").serialize(),
				function (data) {
					$("#" + model).modal("hide");
				}
			)
			.done(function () {
				jQuery.unblockUI();
			});
	}
}

function OpenChangeInsurancePopUp(order, model = "myModal") {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Loading...</h1>',
	});
	jQuery
		.post(
			SITE_URL + "admin/vehicle_reservations/changeinsurancepopup",
			{ order: order },
			function (data) {
				jQuery.unblockUI();
				if (!data.status) {
					alert(data.message);
					return false;
				}
				$("#" + model + " .modal-content").html(data.view);
				$("#" + model)
					.modal("show")
					.find(".modal-dialog")
					.css("width", "850px");
			}
		)
		.done(function () {
			$("#OrderDepositRuleAdminChangeinsurancepopupForm").validate();
		});
	jQuery.unblockUI();
	return false;
}

function SaveInsuranceChanges(model = "myModal") {
	if ($("#OrderDepositRuleAdminChangeinsurancepopupForm").valid()) {
		jQuery.blockUI({
			message:
				'<h1><img src="' +
				SITE_URL +
				'img/select2-spinner.gif" /> Just a moment...</h1>',
		});
		jQuery
			.post(
				SITE_URL + "admin/vehicle_reservations/changeinsurancesave",
				$("#OrderDepositRuleAdminChangeinsurancepopupForm").serialize(),
				function (data) {
					alert(data.message);
					if (data.status) {
						$("#" + model).modal("hide");
					}
				}
			)
			.done(function () {
				jQuery.unblockUI();
			});
	}
}

/**for agreement PDF **/
function getReservationAgreementDoc(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/generateAgrement",
		{ id: orderid },
		function (data) {
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

function uploadAddressProof(userid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery
		.post(
			SITE_URL + "admin/users/address_proof_popup",
			{ userid: userid },
			function (data) {
				$("#plaidModal .modal-content").html(data);
				$("#plaidModal")
					.modal("show")
					.find(".modal-dialog")
					.css("width", "850px");
			}
		)
		.done(function () {
			jQuery.unblockUI();
			$("#AddressProofDoc").fileinput({
				browseLabel: "Browse",
				browseIcon: '<i class="icon-file-plus"></i>',
				uploadIcon: '<i class="icon-file-upload2"></i>',
				removeIcon: false,
				layoutTemplates: {
					icon: '<i class="icon-file-check"></i>',
				},
				uploadUrl: SITE_URL + "admin/users/saveaddressproof", // server upload action
				uploadAsync: true,
				maxFileCount: 1,

				allowedFileExtensions: ["jpeg", "jpg", "png", "pdf"],
				overwriteInitial: false,
				maxFileSize: 10024,
				uploadExtraData: { userid: userid },
				showCancel: false,
				showRemove: false,
			});
		});
	return false;
}

function loadInsurancePopUp(order) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Loading...</h1>',
	});
	jQuery
		.post(
			SITE_URL + "admin/vehicle_reservations/loadinsurancepopup",
			{ order: order },
			function (data) {
				$("#myModal .modal-content").html(data);
				$("#myModal").modal("show").find(".modal-dialog").css("width", "850px");
			}
		)
		.done(function () {
			jQuery.unblockUI();
		});
	return false;
}
function RequeueFailedTrasnfer(order) {
	if (
		!confirm("Are you sure you want to add this transaction in transfer queue?")
	) {
		return;
	}
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Processing...</h1>',
	});

	$.post(
		SITE_URL + "admin/transactions/requeuefailedtransfer",
		{ id: order },
		function (data) {
			if (!data.status) {
				alert(data.message);
			} else {
				jQuery("#listing table tbody tr#failedtr_" + order).remove();
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
	return false;
}

function OpenSignatureDocPopUp(
	InsuranceQuoteId,
	OrderDepositRuleId,
	model = "myModal"
) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery.post(
		SITE_URL + "admin/insurance_provider/docusign/listdocuments",
		{
			quoteid: InsuranceQuoteId,
			OrderDepositRuleId: OrderDepositRuleId,
			model: model,
		},
		function (data) {
			jQuery.unblockUI();
			$("#" + model + " .modal-content").html(data);
			$("#" + model)
				.modal("show")
				.find(".modal-dialog")
				.css("width", "850px");
		}
	);
	jQuery.unblockUI();
	return false;
}
function PullDocusignSignedDocument(
	docusign_envelope_id,
	document_id,
	OrderDepositRuleId,
	document_name,
	model = "statementModal"
) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery.post(
		SITE_URL + "admin/insurance_provider/docusign/fecthdocument",
		{
			docusign_envelope_id: docusign_envelope_id,
			document_id: document_id,
			OrderDepositRuleId: OrderDepositRuleId,
			document_name: document_name,
		},
		function (data) {
			jQuery.unblockUI();
			$("#" + model + " .modal-content").html(data);
			$("#" + model)
				.modal("show")
				.find(".modal-dialog")
				.css("width", "1050px");
		}
	);
	jQuery.unblockUI();
	return false;
}

function getDriverLicense(userid, pick = 1) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/users/getDriverLicense",
		{ userid: userid, pick: pick },
		function (data) {
			jQuery.unblockUI();
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		}
	);
}

/**to get vehicle Inspection doc***/
function getVehicleInspectionDoc(vehicleid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicles/getVehicleInspectionDoc",
		{ vehicleid: vehicleid },
		function (data) {
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		}
	).done(function () {
		jQuery.unblockUI();
	});
}

/**to get booking declaration doc***/
function getDeclarationDoc(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/getDeclarationDoc",
		{ orderid: orderid },
		function (data) {
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		}
	).done(function () {
		jQuery.unblockUI();
	});
}

function openOverDueBookingDetails(order) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery
		.post(
			SITE_URL + "admin/bookings/overdue_booking_details",
			{ order: order },
			function (data) {
				$("#myModal .modal-content").html(data);
				$("#myModal")
					.modal("show")
					.find(".modal-dialog")
					.css("width", "1050px");
			}
		)
		.done(function () {
			jQuery.unblockUI();
		});
	return false;
}

function OpenInsurancePayerListPopUp(order, model = "myModal") {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery
		.post(
			SITE_URL + "admin/insurance/payers/list",
			{ order: order, model: model },
			function (data) {
				$("#" + model + " .modal-content").html(data);
				$("#" + model)
					.modal("show")
					.find(".modal-dialog")
					.css("width", "850px");
			}
		)
		.done(function () {
			jQuery.unblockUI();
		});
	return false;
}
function OpenInsurancePayerListUploadPopUp(
	order,
	model = "myModal",
	isNew = false
) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery
		.post(
			SITE_URL + "admin/insurance/payers/popup",
			{ order: order, model: model, isNew: isNew },
			function (data) {
				$("#" + model + " .modal-content").html(data);
				$("#" + model)
					.modal("show")
					.find(".modal-dialog")
					.css("width", "850px");
			}
		)
		.done(function () {
			jQuery.unblockUI();
			$("#InsurancePayerDeclarationDoc").fileinput({
				browseLabel: "Browse",
				browseIcon: '<i class="icon-file-plus"></i>',
				uploadIcon: '<i class="icon-file-upload2"></i>',
				removeIcon: '<i class="icon-cross3"></i>',
				layoutTemplates: {
					icon: '<i class="icon-file-check"></i>',
				},
				uploadUrl: SITE_URL + "admin/insurance/payers/saveImage", // server upload action
				uploadAsync: true,
				maxFileCount: 1,
				deleteUrl: SITE_URL + "admin/insurance/payers/deleteImage",
				allowedFileExtensions: ["jpeg", "jpg", "png", "pdf"],
				overwriteInitial: false,
				maxFileSize: 10024,
				uploadExtraData: { id: order, type: "declaration_doc" },
				showCancel: false,
				showRemove: false,
			});
			$("#InsurancePayerInsuranceCard").fileinput({
				browseLabel: "Browse",
				browseIcon: '<i class="icon-file-plus"></i>',
				uploadIcon: '<i class="icon-file-upload2"></i>',
				removeIcon: '<i class="icon-cross3"></i>',
				layoutTemplates: {
					icon: '<i class="icon-file-check"></i>',
				},
				uploadUrl: SITE_URL + "admin/insurance/payers/saveImage", // server upload action
				uploadAsync: true,
				maxFileCount: 1,
				deleteUrl: SITE_URL + "admin/insurance/payers/deleteImage",
				allowedFileExtensions: ["jpeg", "jpg", "png", "pdf"],
				overwriteInitial: false,
				maxFileSize: 10024,
				uploadExtraData: { id: order, type: "insurance_card" },
				showCancel: false,
				showRemove: false,
			});
			$(".date").datetimepicker({
				useCurrent: false, //Important! See issue #1075
				format: "YYYY-MM-DD",
			});
		});
	return false;
}

/**Update Vehicle GPS info**/
function updateOdometer(booking) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/updateodometer",
		{ booking: booking },
		function (data) {
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "550px");
		}
	).done(function () {
		jQuery.unblockUI();
		$("form#loadupdateodometer").validate();
	});
}

/**Pull vehicle start odometer save**/
function pullVehicleOdometer() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("form#loadupdateodometer").serialize();
	$.post(
		SITE_URL + "admin/bookings/pullVehicleOdometer",
		params,
		function (data) {
			if (data.status) {
				$("form#loadupdateodometer").find("#TextCurrentOdomter").val(miles);
			} else {
				alert(data.message);
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

/**process booking start odometer save**/
function saveBookingOdometer() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("#loadupdateodometer").serialize();
	$.post(
		SITE_URL + "admin/bookings/saveBookingOdometer",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				alert(data.message);
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

function OpenInspektScanPopUp(orderId, model = "myModal") {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Loading...</h1>',
	});
	jQuery
		.post(
			SITE_URL + "admin/inspekt/inspektdocs/index",
			{ orderid: orderId, model: model },
			function (data) {
				jQuery.unblockUI();
				$("#" + model + " .modal-content").html(data);
				$("#" + model)
					.modal("show")
					.find(".modal-dialog")
					.css("width", "850px")
					.css("height", "650px");
				$("#" + model)
					.modal("show")
					.find(".modal-content")
					.css("width", "850px")
					.css("height", "650px");
			}
		)
		.done(function () {
			jQuery.unblockUI();
		});
	return false;
}

//initiate MVR reports
function getCheckrReport(user, owner) {
	if (!confirm("Are you sure to check this user report by Checkr?")) {
		return;
	}
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = { userid: user, ownerid: owner };
	$.post(
		SITE_URL + "admin/users/checkrreport",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				alert(data.message);
			} else {
				alert(data.message);
			}
		},
		"json"
	);
}

function reGenerateReport(user, owner, booking = "") {
	if (!confirm("Are you sure to check this user report by Checkr?")) {
		return;
	}
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Requesting...</h1>',
		css: { "z-index": "9999" },
	});
	$("#myModal").modal("hide");
	var params = { userid: user};
	$.post(SITE_URL + "admin/mvr_reports/ajaxrequestagain",params,function (data) {
			if (data.status) {
				alert(data.message);
			} else {
				alert(data.message);
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
		getuserdetails(user, owner, booking );
	});
}
function getVehicleScanRequestPopup(booking) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/inspekt/Inspektdocs/openVehicleScanRequestPopup",
		{ booking: booking },
		function (data) {
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "550px");
		}
	).done(function () {
		jQuery.unblockUI();
		$("form#openVehicleScanRequestPopup").validate();
	});
}

function saveVehicleScanPopupRequest() {
	if ($("#openVehicleScanRequestPopup").valid()) {
		jQuery.blockUI({
			message:
				'<h1><img src="' +
				SITE_URL +
				'img/select2-spinner.gif" /> Just a moment...</h1>',
		});
		jQuery
			.post(
				SITE_URL + "admin/inspekt/Inspektdocs/saveVehicleScanPopupRequest",
				$("#openVehicleScanRequestPopup").serialize(),
				function (data) {
					alert(data.message);
					if (data.status) {
						$("#myModal").modal("hide");
					}
				},
				"json"
			)
			.done(function () {
				jQuery.unblockUI();
			});
	}
}

function OpenChangeInsurancePayerPopUp(orderruleid, model = "statementModal") {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Loading...</h1>',
	});
	jQuery
		.post(
			SITE_URL + "admin/vehicle_reservations/changeinsurancetypepopup",
			{ orderruleid: orderruleid },
			function (data) {
				if (!data.status) {
					alert(data.message);
					return false;
				}
				$("#" + model + " .modal-content").html(data.view);
				$("#" + model)
					.modal("show")
					.find(".modal-dialog")
					.css("width", "850px");
			},
			"json"
		)
		.done(function () {
			$("#OrderDepositRuleAdminChangeinsurancetypepopupForm").validate();
			jQuery.unblockUI();
		});
	return false;
}

function SaveInsurancePayerChanges(model = "statementModal") {
	if ($("#OrderDepositRuleAdminChangeinsurancetypepopupForm").valid()) {
		jQuery.blockUI({
			message:
				'<h1><img src="' +
				SITE_URL +
				'img/select2-spinner.gif" /> Saving...</h1>',
		});
		jQuery
			.post(
				SITE_URL + "admin/vehicle_reservations/saveinsurancepayer",
				$("#OrderDepositRuleAdminChangeinsurancetypepopupForm").serialize(),
				function (data) {
					alert(data.message);
					if (data.status) {
						$("#" + model).modal("hide");
						$("#myModal").modal("hide");
					}
				},
				"json"
			)
			.done(function () {
				jQuery.unblockUI();
			});
	}
}

function vehicleReservationCalculateFareMatrix() {
	if (!$("#VehicleOfferForm").valid({ ignore: ":hidden" })) return;
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var formdata = $("#VehicleOfferForm").serialize();
	$.post(
		SITE_URL + "admin/vehicle_reservations/getVehicleDynamicFareMatrix",
		formdata,
		function (obj) {
			$("#VehicleOfferDays").val(obj.days);
			$("#VehicleOfferDayRent").val(obj.dayRent).removeClass("required").addClass("required");
			$("#VehicleOfferInsurance").val(obj.dayInsurance);
			$("#VehicleOfferEmf").val(obj.emf);
			$("#VehicleOfferProgramFee").val(obj.program_fee);
			$("#VehicleOfferTotalInsurance").val(obj.total_insurance);
			$("#VehicleOfferTotalProgramCost").val(obj.total_program_cost);
			$("#VehicleOfferJson").val(JSON.stringify(obj));
			var program = "";
			program += "<ul>";
			program +=
				"<li><strong>Adjusted Program Length:</strong> " +
				obj.days +
				"(days)</li>";
			program +=
				"<li><strong>Total Program Cost:</strong> " +
				obj.total_program_cost +
				"</li>";
			program +=
				"<li><strong>Program Fee:</strong> " + obj.program_fee + "</li>";
			program +=
				"<li><strong>Insurance Cost To Driver:</strong> " +
				obj.total_insurance +
				"</li>";
			program +=
				"<li><strong>Day Insurance:</strong> " + obj.dayInsurance + "</li>";
			program += "<li><strong>Day Rent:</strong> " + obj.dayRent + "</li>";
			program += "<li><strong>EMF Per Day:</strong> " + obj.emf + "</li>";
			program +=
				"<li><strong>Day Rent With Emf:</strong> " + obj.dayEmfRent + "</li>";
			program +=
				"<li><strong>Monthly Miles:</strong> " + obj.month_miles + "</li>";
			program += "<li><strong>Monthly EMF:</strong> " + obj.month_emf + "</li>";
			program += "<li><strong>Weekly Rent:</strong> " + obj.weekRent + "</li>";
			program +=
				"<li><strong>Weekly EMF Rent:</strong> " +
				obj.weekkEmfRent +
				"</li></ul>";
			$("#calculations").html(program);
			$("#VehicleOfferForm button#saveGoalRecalculation").prop("disabled",false);
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
}

function vehicleReservationSaveRecalculation() {
	if (!$("#VehicleOfferForm").valid({ ignore: ":hidden" })) return;
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var formdata = $("#VehicleOfferForm").serialize();
	$.post(
		SITE_URL + "admin/vehicle_reservations/saveGoalRecalculation",
		formdata,
		function (obj) {
			if (!obj.status) {
				alert(obj.message);
			} else {
				alert(obj.message);
				goBack("/admin/vehicle_reservations/index");
			}
		},
		"json"
	).done(function () {
		jQuery.unblockUI();
	});
	return false;
}

function loadStatusChecklistPopup(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Loading...</h1>',
	});
	jQuery
		.post(
			SITE_URL + "admin/vehicle_reservations/loadstatuschecklist",
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

function getPaymentReceipt(paymentid) {
	window.open(
		SITE_URL + "admin/email_queue/email_queues/payment_receipt/" + paymentid,
		"_blank"
	);
	return false;
}
function loadPaymentsPopup(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Loading...</h1>',
	});
	jQuery
		.post(
			SITE_URL + "admin/reports/paymentspopup",
			{ orderid: orderid },
			function (data) {
				jQuery("#plaidModal .modal-content").html(data.data);
				jQuery("#plaidModal")
					.modal("show")
					.find(".modal-dialog")
					.css("width", "550px");
			},
			"json"
		)
		.done(function () {
			jQuery.unblockUI();
		});
	return false;
}

function getVehicleCCMCard(order) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/getVehicleCCMCard",
		{ order: order },
		function (data) {
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		}
	).done(function () {
		jQuery.unblockUI();
	});
}

function sendAxleShareDetails(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
	});
	jQuery.post(
			SITE_URL + "admin/bookings/sendAxleShareDetails",
			{ orderid: orderid },
			function (data) {
				alert(data.message);
			},
			"json"
		)
		.done(function () {
			jQuery.unblockUI();
		});
	return false;
}

function sendDirectAxleLink(orderid) {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Sending...</h1>',
	});
	jQuery.post(SITE_URL + "admin/bookings/sendDirectAxleLink",{ orderid: orderid },
		function (data) {
			alert(data.message);
		},"json").done(function () {
			jQuery.unblockUI();
		});
	return false;
}

function inspektScanReport(orderid) {
    jQuery.blockUI({message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Loading...</h1>'});
    $.post(SITE_URL + "admin/inspekt/inspektdocs/getOrderBasedReport", {orderid: orderid}, function(resp) {
        if (resp.status) {
            $("#myModal .modal-content").html(resp.view);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "850px");
        }else{
            alert(resp.message);
        }
    }, 'json').done(function() {
        jQuery.unblockUI();
    });
    return false;
}


function downloadVehicleImage(vehicleid) {
	
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	
	$.post(SITE_URL + "admin/vehicle_reservations/download_vehicle_images",{vehicleid:vehicleid},
		function (obj) {
			alert(obj.message);
		},"json").done(function () {
		jQuery.unblockUI();
	});
	return false;
}


function bookingInsurancePopup(orderid) {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/bookings/insurancepopup",
		{ orderid: orderid},
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
		}
	);
}

function vehicleSellingOpionsPopup(orderid) {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/vehicleSellingOpions",
		{ orderid: orderid},
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
		}
	);
}
function vehicleSellingOptionAgreeToSellPopup(modalName,orderid) {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/vehicleSellingOpionAgreeToSell",
		{ orderid: orderid},
		function (data) {
			jQuery.unblockUI();
			$("#" + modalName + " .modal-content").html(data);
			$("#" + modalName).modal("show").find(".modal-dialog").css("width", "650px");
		}
	);
}

function saveVehicleAgreeToSell(){
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Saving...</h1>',
        css: {'z-index': '9999'}
    });
    var data = new FormData($("#vehicleSellingOpionAgreeToSellForm").get(0));
    $.ajax({
        url: SITE_URL + "admin/vehicle_reservations/saveVehicleAgreeToSell",
        type: 'post',
        dataType: "JSON",
        data: data,
        processData: false,
        contentType: false,
        success: function (data, status)
        {
            if(!data.status){
                alert(data.message);
            }else{
                alert("Updated successfully");
            }
        },
        complete:function(){
            jQuery.unblockUI();
        }
    });
}

function vehicleFree2moveAgreement(modalName,reference) {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "admin/vehicle_reservations/vehicleFree2moveAgreement",
		{ reference: reference},
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
		}
	);
}

function vehicleSellingOptionNotInterested(orderid) {
    var confirmResult = confirm("Are you sure you want to mark this reservation as Not Interested?");
    if (!confirmResult) {
        return false;
    }
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Saving...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(SITE_URL + "vehicle_reservations/saveVehicleSellingOption",
		{ orderid: orderid,status: 3 },
		function (data) {
			alert(data.message);
			location.reload();
		},"json").done(function () {
            jQuery.unblockUI();
        });
}

function vehicleSellingOptionFindReplacement(orderid) {
    var confirmResult = confirm("Are you sure you want to mark this reservation as find replacement?");
    if (!confirmResult) {
        return false;
    }
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Saving...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(SITE_URL + "vehicle_reservations/saveVehicleSellingOption",
		{ orderid: orderid,status: 4 },
		function (data) {
			alert(data.message);
			location.reload();
		},"json").done(function () {
            jQuery.unblockUI();
        });
}