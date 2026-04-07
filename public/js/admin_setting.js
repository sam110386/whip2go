function initSetting() {
	const allProviders = ['passtime', 'geotab', 'geotabkeyless', 'ituran', 'onestepgps', 'smartcar', 'autopi'];

	$("#CsSettingPasstime").change(function () {
		const selectedVal = $(this).val();
		const gpsProvider = $("#CsSettingGpsProvider").val();
		allProviders.forEach(provider => {
			const showFor = [selectedVal];
			if (selectedVal === 'geotabkeyless') showFor.push('geotab');
			if (showFor.includes(provider)) {
				$(`div.${provider}`).show();
			} else if (gpsProvider !== provider) {
				$(`div.${provider}`).hide();
			}
		});
		const getRelData = (target) => {
			if (selectedVal === target && gpsProvider === target) return 'both';
			if (selectedVal === target) return 'starter';
			if (gpsProvider === target) return 'gps';
			return '';
		};
		$("#syncDeviceWithGeotab").attr("rel-data", getRelData('geotab'));
		$("#sycnAutoPiVehicle").attr("rel-data", getRelData('autopi'));
	});
	$("#CsSettingGpsProvider").change(function () {
		const selectdVal = $(this).val();
		const gpsPasstime = $("#CsSettingPasstime").val();
		allProviders.forEach(provider => {
			const shFor = [selectdVal];
			if (selectdVal === 'geotabkeyless') shFor.push('geotab');
			if (shFor.includes(provider)) {
				$(`div.${provider}`).show();
			} else if (gpsPasstime !== provider) {
				$(`div.${provider}`).hide();
			}
		});

		const getRlData = (target) => {
			if (selectdVal === target && gpsPasstime === target) return 'both';
			if (selectdVal === target) return 'gps';
			if (gpsPasstime === target) return 'starter';
			return '';
		};
		$("#syncDeviceWithGeotab").attr("rel-data", getRlData('geotab'));
		$("#sycnAutoPiVehicle").attr("rel-data", getRlData('autopi'));
	});
	$("#CsSettingPasstime").trigger("change");
	$("#CsSettingGpsProvider").trigger("change");
}
$(document).ready(function () {
	if ($("#SettingAdminIndexForm").length && typeof $.fn.validate === "function") {
		$("#SettingAdminIndexForm").validate();
	}
	initSetting();
});

function validateGeoTab() {
	if (
		$("#CsSettingGeotabServer").val().length == "" ||
		$("#CsSettingGeotabUser").val().length == "" ||
		$("#CsSettingGeotabPwd").val().length == ""
	) {
		alert("Please fill all details");
		return false;
	}
	if (typeof $.blockUI === "function") {
		$.blockUI({
			message: '<h1><img src="/img/select2-spinner.gif" /> Sending...</h1>',
			css: { "z-index": "9999" },
		});
	}

	var data = {
		geotab: $("#CsSettingPasstime").val(),
		server: $("#CsSettingGeotabServer").val(),
		username: $("#CsSettingGeotabUser").val(),
		pwd: $("#CsSettingGeotabPwd").val(),
		database: $("#CsSettingGeotabDb").val(),
	};
	$.post("/admin/settings/validateGeotab", data, function (resp) {
		if (typeof $.unblockUI === "function") {
			$.unblockUI();
		}
		if (resp.status) {
			if (resp.data && resp.data.credentials && resp.data.credentials.database) {
				$("#CsSettingGeotabDb").val(resp.data.credentials.database);
			}
		} else {
			$("#CsSettingGeotabServer").val("");
			$("#CsSettingGeotabUser").val("");
			$("#CsSettingGeotabPwd").val("");
			$("#CsSettingGeotabDb").val("");
			alert(resp.message);
		}
	}, "json");
}

function validateOneStepGPSKey() {
	if ($("#CsSettingOnestepgps").val().length == "") {
		alert("Please fill the key");
		return false;
	}
	if (typeof $.blockUI === "function") {
		$.blockUI({
			message: '<h1><img src="/img/select2-spinner.gif" /> Sending...</h1>',
			css: { "z-index": "9999" },
		});
	}
	var data = { key: $("#CsSettingOnestepgps").val() };
	$.post("/admin/settings/validateOneStepGPSKey", data, function (resp) {
		if (typeof $.unblockUI === "function") {
			$.unblockUI();
		}
		if (resp.status) {
			alert("GPS key seems fine");
		} else {
			$("#CsSettingOnestepgps").val("");
			alert(resp.message);
		}
	}, "json");
}

function syncVehicleWithGeotab() {
	if (
		$("#CsSettingGeotabServer").val().length == "" ||
		$("#CsSettingGeotabUser").val().length == "" ||
		$("#CsSettingGeotabPwd").val().length == ""
	) {
		alert("Please fill all details");
		return false;
	}
	if (typeof $.blockUI === "function") {
		$.blockUI({
			message: '<h1><img src="/img/select2-spinner.gif" /> Sending...</h1>',
			css: { "z-index": "9999" },
		});
	}

	var data = {
		server: $("#CsSettingGeotabServer").val(),
		username: $("#CsSettingGeotabUser").val(),
		pwd: $("#CsSettingGeotabPwd").val(),
		database: $("#CsSettingGeotabDb").val(),
		type: $("#syncDeviceWithGeotab").attr("rel-data"),
		userid: $("#CsSettingEncodeUserId").val(),
	};
	$.post("/admin/settings/syncDeviceWithGeotab", data, function (resp) {
		if (typeof $.unblockUI === "function") {
			$.unblockUI();
		}
		if (resp.status) {
			alert("Vehicles Synched successfully");
		} else {
			alert(resp.message);
		}
	}, "json");
}
function syncVehicleWithOnestep() {
	if ($("#CsSettingOnestepgps").val().length == "") {
		alert("Please fill all details");
		return false;
	}
	let conf = confirm(
		"Please make sure you saved this page before sync vehicle data. All saved setting will be used, to sync with GPS provider api."
	);
	if (!conf) {
		return false;
	}
	if (typeof $.blockUI === "function") {
		$.blockUI({
			message: '<h1><img src="/img/select2-spinner.gif" /> Sending...</h1>',
			css: { "z-index": "9999" },
		});
	}

	var data = {
		apikey: $("#CsSettingOnestepgps").val(),
		userid: $("#CsSettingEncodeUserId").val(),
	};
	$.post("/admin/settings/syncVehicleWithOnestep", data, function (resp) {
		if (typeof $.unblockUI === "function") {
			$.unblockUI();
		}
		if (resp.status) {
			alert("Vehicles Synched successfully");
		} else {
			alert(resp.message);
		}
	}, "json");
}
function SyncVehicleAllowedMiles() {
	if (
		$("#CsSettingAllowedMiles").val().length == "" ||
		$("#CsSettingAllowedMiles").val() === 0
	) {
		alert("Please fill valid value");
		return false;
	}
	if (!confirm("Are you sure that you want to update all existing vehicles?")) {
		return false;
	}
	if (typeof $.blockUI === "function") {
		$.blockUI({
			message: '<h1><img src="/img/select2-spinner.gif" /> Sending...</h1>',
			css: { "z-index": "9999" },
		});
	}

	var data = {
		allowed_miles: $("#CsSettingAllowedMiles").val(),
		userid: $("#CsSettingEncodeUserId").val(),
	};
	$.post("/admin/settings/syncVehicleAllowedMiles", data, function (resp) {
		if (typeof $.unblockUI === "function") {
			$.unblockUI();
		}
		if (resp.status) {
			alert("Vehicles Synched successfully");
		} else {
			alert(resp.message);
		}
	}, "json");
}
function SyncVehicleFinancing() {
	if ($("#CsSettingVehicleFinancing").val().length == "") {
		alert("Please fill valid value");
		return false;
	}
	if (!confirm("Are you sure that you want to update all existing vehicles?")) {
		return false;
	}
	if (typeof $.blockUI === "function") {
		$.blockUI({
			message: '<h1><img src="/img/select2-spinner.gif" /> Sending...</h1>',
			css: { "z-index": "9999" },
		});
	}

	var data = {
		finance: $("#CsSettingVehicleFinancing").val(),
		userid: $("#CsSettingEncodeUserId").val(),
	};
	$.post("/admin/settings/syncVehicleFinancing", data, function (resp) {
		if (typeof $.unblockUI === "function") {
			$.unblockUI();
		}
		if (resp.status) {
			alert("Vehicles Synched successfully");
		} else {
			alert(resp.message);
		}
	}, "json");
}

function SyncVehicleAddress() {
	if (!confirm("Are you sure that you want to update all existing vehicles?")) {
		return false;
	}
	if (typeof $.blockUI === "function") {
		$.blockUI({
			message: '<h1><img src="/img/select2-spinner.gif" /> Sending...</h1>',
			css: { "z-index": "9999" },
		});
	}
	var data = $("#SettingAdminIndexForm").serialize();
	$.post("/admin/settings/syncVehicleAddress", data, function (resp) {
		if (typeof $.unblockUI === "function") {
			$.unblockUI();
		}
		if (resp.status) {
			alert("Vehicles Synched successfully");
		} else {
			alert(resp.message);
		}
	}, "json");
}
function SyncVehicleDefaultAddress() {
	if (!confirm("Are you sure that you want to update all existing vehicles?")) {
		return false;
	}
	if (typeof $.blockUI === "function") {
		$.blockUI({
			message: '<h1><img src="/img/select2-spinner.gif" /> Sending...</h1>',
			css: { "z-index": "9999" },
		});
	}
	var data = $("#SettingAdminIndexForm").serialize();
	$.post("/admin/settings/syncVehicleDefaultAddress", data, function (resp) {
		if (typeof $.unblockUI === "function") {
			$.unblockUI();
		}
		if (resp.status) {
			alert("Vehicles Synched successfully");
		} else {
			alert(resp.message);
		}
	}, "json");
}

function updateFareType(field) {
	field = field || "fare_type";
	var data;
	if (field == "roadside_assistance_included") {
		data = {
			user_id: $("#CsSettingUserId").val(),
			roadside_assistance_included: $("#DepositTemplateRoadsideAssistanceIncluded").val(),
			field: field,
		};
	}
	if (field == "maintenance_included_fee") {
		data = {
			user_id: $("#CsSettingUserId").val(),
			maintenance_included_fee: $("#DepositTemplateMaintenanceIncludedFee").val(),
			field: field,
		};
	}

	if (typeof $.blockUI === "function") {
		$.blockUI({
			message: '<h1><img src="/img/select2-spinner.gif" /> Sending...</h1>',
			css: { "z-index": "9999" },
		});
	}
	$.post("/admin/deposit_templates/updateFareType", data, function (resp) {
		if (typeof $.unblockUI === "function") {
			$.unblockUI();
		}
		if (resp.status) {
			alert("Vehicles synched successfully");
		} else {
			alert(resp.message);
		}
	}, "json");
}

function pullAutoPiVehicle() {
	if (($("#CsSettingAutopiToken").val()).length === 0) {
		alert("Please fill all details");
		return false;
	}
	if (typeof $.blockUI === "function") {
		$.blockUI({
			message: '<h1><img src="/img/select2-spinner.gif" /> Sending...</h1>',
			css: { "z-index": "9999" },
		});
	}
	var params = {
		autopi_token: $("#CsSettingAutopiToken").val(),
		type: $("#sycnAutoPiVehicle").attr("rel-data"),
		userid: $("#CsSettingEncodeUserId").val(),
	};
	$.post("/admin/settings/pullDevicesFromAutoPi", params, function (resp) {
		if (resp.status) {
			alert("Vehicles Synched successfully");
		} else {
			alert(resp.message);
		}
	}, "json").done(function () {
		if (typeof $.unblockUI === "function") {
			$.unblockUI();
		}
	});
}
