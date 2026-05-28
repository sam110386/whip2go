function featuredVehicleOpenVariantPopup(){
    if(!jQuery("#VehicleAdminAddForm").valid()){
        return;
    }
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Loading...</h1>',
        css: {'z-index': '9999'}
    });
    $.post(SITE_URL + "admin/vehicle/featured_vehicles/loadAttributePopup", {}, function (data) {
        $("#modelsidebar .modal-content").html(data);
        $ ("#modelsidebar").modal("show").find(".modal-dialog").css("width", "100%").css("height", "100%");
    }).done(function(){
        jQuery.unblockUI();
    });
}

function featuredVehicleAttributeStep1(){
    if(!jQuery("#FeaturedVehicleAdminLoadAttributePopupForm").valid()){
        return;
    }
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Loading...</h1>',
        css: {'z-index': '9999'}
    });
    var params=$("#FeaturedVehicleAdminLoadAttributePopupForm").serialize();
    $.post(SITE_URL + "admin/vehicle/featured_vehicles/loadAttributeStep2Popup", params, function (data) {
        $("#modelsidebar .modal-content").html(data);
        $("#modelsidebar").modal("show").find(".modal-dialog").css("width", "100%").css("height", "100%");
    }).done(function(){
        jQuery.unblockUI();
        $('.bootstrap-select').selectpicker();
    });
}

function featuredVehicleAttributeStep2(){
    if(!jQuery("#FeaturedVehicleAdminLoadAttributeStep2PopupForm").valid()){
        return;
    }
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Loading...</h1>',
        css: {'z-index': '9999'}
    });
    var params=$("#FeaturedVehicleAdminLoadAttributeStep2PopupForm").serialize()+'&stock_no='+$("#VehicleStockNo").val()+'&msrp='+$("#VehicleMsrp").val()+"&premium_msrp="+$("#VehiclePremiumMsrp").val()+"&vin="+$("#VehicleVinNo").val();
    $.post(SITE_URL + "admin/vehicle/featured_vehicles/loadAttributeStep3List", params, function (data) {
        $("#variantVehicleBlockWrapper").html(data);
        $("#modelsidebar").modal("hide");
    }).done(function(){
        jQuery.unblockUI();
    });
}

function featuredVehicleAddAttribute_More(v) {
    var elem = parseInt($("#VehicleAttributes").attr('rel-attributes'));
    if (v) {
        if (elem === 5) {
            alert("Sorry, you cant add more than 5 reccords");
            return;
        }
        elem++;
        var element = '<div class="form-group" id="ele-' + elem + '">' +
            '<label class="col-lg-2 control-label text-bold">Attribute Name #'+elem+':<font class="requiredField">*</font></label>\
                <div class="col-lg-6">\
                <input name="data[FeaturedVehicle][attribute]['+elem+']" class="required form-control alphanumericwithspace" placeholder="Like Color, Trim..." type="text">\
                </div>\
            <div class="col-lg-4"><a href="javascript:void(0)" onclick="featuredVehicleAddAttribute_More(false)"><i class=" icon-minus-circle2 icon-2x"></i></a></div></div>';
        $("#VehicleAttributes").append(element);
    } else {
        $("#VehicleAttributes #ele-" + elem).remove();
        elem--;
    }
    $("#VehicleAttributes").attr('rel-attributes', elem);
}

function featuredVehicleAddVariantPopup(parentid){
    if(!jQuery("#VehicleAdminAddForm").valid()){
        return;
    }
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Loading...</h1>',
        css: {'z-index': '9999'}
    });
    $.post(SITE_URL + "admin/vehicle/featured_vehicles/loadNewVariant", {parentid:parentid}, function (data) {
        $("#modelsidebar .modal-content").html(data);
        $ ("#modelsidebar").modal("show").find(".modal-dialog").css("width", "100%").css("height", "100%");
    }).done(function(){
        jQuery.unblockUI();
    });
}

function removeVariationRow(stock_no,variantid=null){
    var conf=confirm("Are you sure you want to delete this record?");
    if(!conf){
        return;
    }

    $("#variantVehicleBlockWrapper tr#row" + stock_no).remove();
    if(variantid){
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> processing...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL + "admin/vehicle/featured_vehicles/deleteVariant", {variantid:variantid}, function (data) {
            alert(data.message);
        },'json').done(function(){
            jQuery.unblockUI();
        });
    }
}

function addExistingStep2(){
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Loading...</h1>',
        css: {'z-index': '9999'}
    });
    var params=$("#FeaturedVehicleAdminLoadNewVariantForm").serialize();
    $.post(SITE_URL + "admin/vehicle/featured_vehicles/addExistingStep2", params, function (data) {
        $("#modelsidebar .modal-content").html(data);
        $("#modelsidebar").modal("show").find(".modal-dialog").css("width", "100%").css("height", "100%");
    }).done(function(){
        jQuery.unblockUI();
    });
}

function addExistingStep3(){
    if(!jQuery("#FeaturedVehicleAdminAddExistingStep2Form").valid()){
        return;
    }
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Loading...</h1>',
        css: {'z-index': '9999'}
    });
    var params=$("#FeaturedVehicleAdminAddExistingStep2Form").serialize();
    $.post(SITE_URL + "admin/vehicle/featured_vehicles/addExistingStep3", params, function (data) {
        $("#variantVehicleBlockWrapper").html(data);
        $("#modelsidebar").modal("hide");
    }).done(function(){
        jQuery.unblockUI();
    });
}