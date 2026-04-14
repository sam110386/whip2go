/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function startBooking(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "bookings/startBooking",
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
					.load(SITE_URL + "bookings/load_single_row", { orderid: orderid });
			} else {
				alert(data.message);
			}
		},
		"json",
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
		SITE_URL + "bookings/loadcancelBooking",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
		},
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
		SITE_URL + "bookings/cancelBooking",
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
		"json",
	);
}
function completeBooking(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "bookings/loadcompleteBooking",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
		},
	);
}
function processComplete(btn) {
	$(btn).prop("disabled", true);
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("#completeForm").serialize();
	$.post(
		SITE_URL + "bookings/completeBooking",
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
		"json",
	);
}
function downloadBookingDoc(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "bookings/getinsurancepopup",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
		},
	);
}

function getinsurancedoc(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "bookings/getinsurancetoken",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		},
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
		SITE_URL + "vehicles/getVehicleRegistration",
		{ vehicleid: vehicleid },
		function (data) {
			jQuery.unblockUI();
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		},
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
		SITE_URL + "message_histories/loadmessagehistory",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "800px");
		},
	);
}
function openRenterHistory(renterid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "report_renters/history",
		{ renterid: renterid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "800px");
		},
	);
}

/**to get review popup, on waiting review page**/
function getreviewpopup(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "booking_reviews/reviewpopup",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
		},
	);
}

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
		SITE_URL + "message_histories/loadnewmessage",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "800px");
		},
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
		SITE_URL + "message_histories/sendnewmessage",
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
		"json",
	);
}

/***report page js functions***/
function openTripDetails(tripId) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery.post(SITE_URL + "reports/details/" + tripId, {}, function (data) {
		jQuery.unblockUI();
		$("#myModal .modal-content").html(data);
		$("#myModal").modal("show").find(".modal-dialog").css("width", "1050px");
	});
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
		SITE_URL + "reports/autorenewddetails/" + tripId,
		{},
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "1050px");
		},
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
		SITE_URL + "booking_reviews/reviewimages/" + orderid,
		{},
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show");
		},
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
		SITE_URL + "reports/loadsubbooking/" + orderid,
		{},
		function (data) {
			if (data.status == "success") {
				jQuery("tr#tr_" + data.booking_id).after(data.data);
				jQuery("tr#tr_" + data.booking_id).attr("rel-parent", "yes");
			}
			jQuery.unblockUI();
		},
		"json",
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
		SITE_URL + "vehicles/loadVehicleStatus",
		{ vehicleid: vehicleid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
		},
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
		SITE_URL + "vehicles/changeVehicleStatus",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				$("#myModal").modal("hide");
				$("table.vehiclelist")
					.find("tr#" + data.vehicleid)
					.load(SITE_URL + "vehicles/load_single_row", {
						vehicleid: data.vehicleid,
					});
			} else {
				alert(data.message);
			}
		},
		"json",
	);
}

/**for agreement PDF**/
function getagreement(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "bookings/getagreement",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		},
	);
}

/**for Change attached CC info**/
function changeccdetails(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "bookings/changeccdetails",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
		},
	);
}
/**for Change attached CC info save**/
function processchangeccdetails() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("#changeccdetails").serialize();
	$.post(
		SITE_URL + "bookings/processchangeccdetails",
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
		"json",
	);
}

/***vehicle Reservation page related functions***/
function acceptReservation(res_id) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "vehicle_reservations/vehicle",
		{ res_id: res_id },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "100%");
			jQuery(
				".inspection_exp_date,.state_insp_exp_date,.reg_name_exp_date,.insurance_policy_exp_date,.insurance_policy_date",
			).datepicker({
				dateFormat: "mm/dd/yy",
				changeMonth: true,
				changeYear: true,
			});
			jQuery("#VehicleRental").change(function () {
				if (jQuery(this).val() == "hr") {
					jQuery("#dayblk").hide();
					jQuery("#dayblk").find("input").val(0);
					jQuery("#hrblk").find("input").val(jQuery(this).attr("rel_hr"));
					jQuery("#hrblk").show();
				} else {
					jQuery("#hrblk").hide();
					jQuery("#hrblk").find("input").val(0);
					jQuery("#dayblk").find("input").val(jQuery(this).attr("rel_day"));
					jQuery("#dayblk").show();
				}
			});
		},
	);
}
function cancelReservation(lease_id) {
	swal(
		{
			title: "",
			text: "Are you sure you want to cancel this?",
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
					SITE_URL + "vehicle_reservations/markBookingCancel",
					{ lease_id: lease_id },
					function (data) {
						jQuery.unblockUI();
						if (data.status) {
							jQuery(
								"#postsPaging table tbody tr#tripRow" + data.result.lease_id,
							).remove();
						} else {
							swal("Error!", data.message, "error");
						}
					},
					"json",
				);
			}
		},
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
		SITE_URL + "vehicle_reservations/createBooking",
		{ lease_id: lease_id },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data).show();
			$("#myModal").modal("show").find(".modal-dialog").css("width", "1100px");
			initializeDate();
		},
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
		SITE_URL + "vehicle_reservations/saveVehicleBooking",
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
					SITE_URL + "vehicle_reservations/markBookingCompleted",
					{ lease_id: lease_id },
					function (data) {
						jQuery(
							"#postsPaging table tbody tr#tripRow" + data.lease_id,
						).remove();
					},
				);
			} else {
				swal("Error!", data.message, "error");
			}
		},
		"json",
	);
	return false;
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
		SITE_URL + "bookings/loadvehicleexpiretime",
		{ booking: booking },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "450px");
			$("#TextPasstimeThreshold").datetimepicker({});
		},
	);
}
/**process Change attached CC info save**/
function processVehicleLockTime() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("#loadvehicleexpiretime").serialize();
	$.post(
		SITE_URL + "bookings/processvehicleexpiretime",
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
		"json",
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
		SITE_URL + "mvr_reports/report",
		{ reportid: reportid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "450px");
		},
	);
}

/***MVR reports page related functions***/
function getVehicleReport(reportid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "mvr_reports/vehiclereport",
		{ reportid: reportid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "450px");
		},
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
		SITE_URL + "mvr_reports/loadactivebooking",
		{ userid: userid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
		},
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
		SITE_URL + "mvr_reports/cancelMvrBooking",
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
		"json",
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
		SITE_URL + "mvr_reports/cancelMvrResevationBooking",
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
		"json",
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
		SITE_URL + "payment_logs/index",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "850px");
		},
	);
}

/****Update booking Payment Setting*/
function updateOrderDepositRules(bookingid) {
	if (
		!confirm(
			"Are you sure you want to update payment setting for this booking?",
		)
	) {
		return false;
	}
	//location.href=SITE_URL + "order_deposit_rules/update/"+bookingid;
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
		SITE_URL + "uber/index/getbooking",
		{ orderid: tripid },
		function (data) {
			ele.html(data.booking);
		},
		"json",
	).done(function () {
		ele.unblock();
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
		SITE_URL + "bookings/loadvehiclegps",
		{ booking: booking },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "550px");
		},
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
		SITE_URL + "bookings/updatevehiclegps",
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
		"json",
	);
}

/***function to get user details, on pending trip listing page***/
function getuserdetails(userid, booking = "") {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "vehicle_reservations/getuserdetails",
		{ userid: userid, booking: booking },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "550px");
			//initialize editable element
			$("#provenIncome").editable({
				success: function (response, newValue) {
					if (response.status == "error") return response.msg;
				},
			});
		},
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
		SITE_URL + "vehicle_reservations/getplaidrecord",
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
		"json",
	);
}
/***function to get user bank statement, on pending trip listing page***/
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
/***function to get user bank statement, on pending trip listing page***/
function loadbankbalance(plaidtoken, userid) {
	$("#bankdetail .plaidbalance").each(function (index) {
		var ele = $(this);
		showUIBlocker(ele.parent());
		console.log(index + ": " + ele.attr("rel-token"));
		var acccountid = ele.attr("rel-token");
		$.post(
			SITE_URL + "vehicle_reservations/getplaidbalance",
			{ userid: userid, plaid_token: plaidtoken, acccountid: acccountid },
			function (resp) {
				ele.html(resp.balance);
				console.log("balance: " + resp.balance);
			},
			"json",
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
/***function to get user bank statement, on pending trip listing page***/
function loadbankstatement(plaidtoken, userid, acccountid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "vehicle_reservations/bankstatement",
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
		"json",
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
		SITE_URL + "vehicle_reservations/checkodometer",
		{ vehicleid: vehicleid },
		function (resp) {
			if (resp.status === "success") {
				$("#gpstester .messagedisplay").html(resp.html);
			} else {
				alert(resp.message);
			}
		},
		"json",
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
		SITE_URL + "vehicle_reservations/checkStarterInterrupt",
		{ vehicleid: vehicleid, orderid: orderid },
		function (resp) {
			if (resp.status === "success") {
				$("#gpstester .messagedisplay").html(resp.html);
			} else {
				alert(resp.message);
			}
		},
		"json",
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
		SITE_URL + "vehicle_reservations/disableStaterInterrupt",
		{ vehicleid: vehicleid, orderid: orderid, disable: 1 },
		function (resp) {
			if (resp.status) {
				$("#starterWorkswrapper .starterWorkOptions").show();
				$("#starterWorkswrapper .starterWorks").hide();
			} else {
				alert(resp.message);
			}
		},
		"json",
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
		SITE_URL + "vehicle_reservations/staterInterruptWorks",
		{ vehicleid: vehicleid, orderid: orderid },
		function (resp) {
			if (resp.status === "success") {
				$("#starterWorkswrapper .starterWorks").show();
				$("#starterWorkswrapper .starterWorkOptions").hide();
			} else {
				alert(resp.message);
			}
		},
		"json",
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
		SITE_URL + "vehicle_reservations/disableStaterInterrupt",
		{ vehicleid: vehicleid, orderid: orderid, disable: 0 },
		function (resp) {
			if (resp.status) {
				$("#starterWorkswrapper .starterEnableWorkOptions").show();
			} else {
				alert(resp.message);
			}
		},
		"json",
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
		SITE_URL + "vehicles/getvehicledetails",
		{ vehicleid: vehicleid, orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "1150px");
		},
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
		url: SITE_URL + "vehicles/updateVehicleDetails",
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
function changeDatetime(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "vehicle_reservations/changeDatetime",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "550px");
			initializeDate();
		},
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
		SITE_URL + "vehicle_reservations/updateDatetime",
		fromdata,
		function (data) {
			if (!data.status) {
				alert(data.message);
			} else {
				alert(data.message);
				$("#myModal").modal("hide");
			}
		},
		"json",
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
		SITE_URL + "vehicles/getVehicleGps",
		{ vehicleid: vehicleid },
		function (data) {
			if (!data.status) {
				alert(data.message);
			} else {
				var $elem = $("#updateVehicleDetails #" + type);
				$elem.editable("setValue", data.gps_serialno).editable("toggle");
			}
		},
		"json",
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
		SITE_URL + "vehicle_reservations/changeStatus",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "550px");
		},
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
		SITE_URL + "vehicle_reservations/changeSaveStatus",
		fromdata,
		function (data) {
			if (!data.status) {
				alert(data.message);
			} else {
				alert(data.message);
				$("#myModal").modal("hide");
				$("#postsPaging table")
					.find("tr#tripRow" + data.orderid)
					.load(SITE_URL + "vehicle_reservations/singleload", {
						orderid: data.orderid,
					});
			}
		},
		"json",
	).done(function () {
		jQuery.unblockUI();
	});
}

function openBookingDetails(order) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery.post(
		SITE_URL + "report/pastdues/details",
		{ order: order },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "1050px");
		},
	);
	jQuery.unblockUI();
	return false;
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
		SITE_URL + "vehicle_reservations/vehicleReservationLog",
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
		"json",
	);
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
		SITE_URL + "smart_car/smart_cars/getbattery",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				$("#myModal span.batteryspan").html(data.battery);
			} else {
				alert(data.message);
			}
		},
		"json",
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
		SITE_URL + "bookings/geotabkeylesslock",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				alert("Request processed successfully");
			} else {
				alert(data.message);
			}
		},
		"json",
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
		SITE_URL + "bookings/geotabkeylessunlock",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				alert("Request processed successfully");
			} else {
				alert(data.message);
			}
		},
		"json",
	);
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
		SITE_URL + "users/getDriverLicense",
		{ userid: userid, pick: pick },
		function (data) {
			jQuery.unblockUI();
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		},
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
		SITE_URL + "vehicles/getVehicleInspectionDoc",
		{ vehicleid: vehicleid },
		function (data) {
			jQuery.unblockUI();
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		},
	);
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
		SITE_URL + "bookings/getDeclarationDoc",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		},
	);
}
function openOverDueBookingDetails(order) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Just a moment...</h1>',
	});
	jQuery.post(
		SITE_URL + "bookings/overdue_booking_details",
		{ order: order },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "1050px");
		},
	);
	jQuery.unblockUI();
	return false;
}

/**Load booking odometer popup**/
function updateOdometer(booking) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "bookings/updateodometer",
		{ booking: booking },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "550px");
		},
	);
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
		SITE_URL + "bookings/pullVehicleOdometer",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				$("form#loadupdateodometer").find("#TextCurrentOdomter").val(miles);
			} else {
				alert(data.message);
			}
		},
		"json",
	);
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
		SITE_URL + "bookings/saveBookingOdometer",
		params,
		function (data) {
			jQuery.unblockUI();
			if (data.status) {
				alert(data.message);
			} else {
				alert(data.message);
			}
		},
		"json",
	);
}

function getPaymentReceipt(paymentid) {
	window.open(
		SITE_URL + "email_queue/email_queues/payment_receipt/" + paymentid,
		"_blank",
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
			SITE_URL + "reports/paymentspopup",
			{ orderid: orderid },
			function (data) {
				jQuery("#plaidModal .modal-content").html(data.data);
				jQuery("#plaidModal")
					.modal("show")
					.find(".modal-dialog")
					.css("width", "550px");
			},
			"json",
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
		SITE_URL + "bookings/getVehicleCCMCard",
		{ order: order },
		function (data) {
			if (!data.status) {
				alert(data.message);
			} else {
				window.open(data.result.file);
			}
		},
	).done(function () {
		jQuery.unblockUI();
	});
}

function vehicleSellingOpionsPopup(orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "vehicle_reservations/vehicleSellingOpions",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#myModal .modal-content").html(data);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "650px");
		},
	);
}
function vehicleSellingOptionAgreeToSellPopup(modalName, orderid) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "vehicle_reservations/vehicleSellingOpionAgreeToSell",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#" + modalName + " .modal-content").html(data);
			$("#" + modalName)
				.modal("show")
				.find(".modal-dialog")
				.css("width", "650px");
		},
	);
}

function saveVehicleAgreeToSell() {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Saving...</h1>',
		css: { "z-index": "9999" },
	});
	var data = new FormData($("#vehicleSellingOpionAgreeToSellForm").get(0));
	$.ajax({
		url: SITE_URL + "vehicle_reservations/saveVehicleAgreeToSell",
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

function vehicleSellingOptionFindReplacementPopup(modalName, orderid) {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(SITE_URL + "vehicle_reservations/vehicleSellingOpionFindReplacement",
		{ orderid: orderid },
		function (data) {
			jQuery.unblockUI();
			$("#" + modalName + " .modal-content").html(data);
			$("#" + modalName).modal("show").find(".modal-dialog").css("width", "650px");
		},
	);
}
function vehicleSellingOptionFindReplacement() {
    var confirmResult = confirm("Are you sure you want to replace the selected vehicle for this reservation?");
    if (!confirmResult) {
        return false;
    }
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Saving...</h1>',
		css: { "z-index": "9999" },
	});
	var params = $("form#updateVehicleDetails").serialize();
	$.post(SITE_URL + "vehicle_reservations/saveVehicleSellingOption",
		params+"&vehicle_replacement=1&status=4",
		function (data) {
			alert(data.message);
            location.reload();
		},"json").done(function () {
            jQuery.unblockUI();
        });
}

function getVehicleScanRequestPopup(booking,isReservation = false) {
	jQuery.blockUI({
		message:
			'<h1><img src="' +
			SITE_URL +
			'img/select2-spinner.gif" /> Sending...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(
		SITE_URL + "inspekt/Inspektdocs/openVehicleScanRequestPopup",
		{ booking: booking, isReservation: isReservation?1:0 },
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
				SITE_URL + "inspekt/Inspektdocs/saveVehicleScanPopupRequest",
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