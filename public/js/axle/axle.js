function getAxlePolicyDetails(orderid,myModel='myModal') {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(SITE_URL + "admin/axle/axledocs/policyDetails",{ orderid: orderid },function (data) {
        $("#"+myModel+" .modal-content").html(data.html);
		$("#"+myModel).modal("show").find(".modal-dialog").css("width", "650px");
			
	}).done(function(){
        jQuery.unblockUI();
    });
}

function getAxleAccountDetails(orderid) {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(SITE_URL + "admin/axle/axledocs/accountDetails",{ orderid: orderid },function (data) {
        alert(data.message);
			
	},'json').done(function(){
        jQuery.unblockUI();
    });
}

function axlePolicyDetailsPopup(orderid,myModel='myModal') {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(SITE_URL + "admin/axle/axledocs/policyDetailsPopup",{ orderid: orderid },function (data) {
        $("#"+myModel+" .modal-content").html(data.html);
		$("#"+myModel).modal("show").find(".modal-dialog").css("width", "650px");
			
	}).done(function(){
        jQuery.unblockUI();
    });
}

function axlePolicyAcceptSave() {
	if(!$("#AxleStatusAdminPolicyDetailsPopupForm").valid()){
		return false;
	}
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	var params=$("#AxleStatusAdminPolicyDetailsPopupForm").serialize();
	$.post(SITE_URL + "admin/axle/axledocs/acceptsave",params,function (data) {
        alert(data.message);
		axleSingleLoad(data.orderid)
	}).done(function(){
        jQuery.unblockUI();
		$("#myModal").modal("hide");
    });
}

function axlePolicySave() {
	if(!$("#AxleStatusAdminPolicyDetailsPopupForm").valid()){
		return false;
	}
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	var params=$("#AxleStatusAdminPolicyDetailsPopupForm").serialize();
	$.post(SITE_URL + "admin/axle/axledocs/policysave",params,function (data) {
        alert(data.message);
		axleSingleLoad(data.orderid);
	}).done(function(){
        jQuery.unblockUI();
		$("#myModal").modal("hide");
    });
}

function axleSingleLoad(orderid) {
	$("#listing table").find("tr#tripRow" + orderid).load(SITE_URL + "admin/axle/axledocs/singleload", {orderid: orderid});
}

function axlePolicyDisconnect(orderid) {
	jQuery.blockUI({
		message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> loading...</h1>',
		css: { "z-index": "9999" },
	});
	$.post(SITE_URL + "admin/axle/axledocs/disconnect",{orderid:orderid},function (data) {
        alert(data.message);
		axleSingleLoad(orderid)
	}).done(function(){
        jQuery.unblockUI();
    });
}