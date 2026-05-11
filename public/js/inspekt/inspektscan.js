function loadReportDetail(caseid) {
    jQuery.blockUI({message:'<h1><img src="' +SITE_URL +'img/select2-spinner.gif" /> Loading...</h1>'});
    $.post(SITE_URL + "admin/inspekt/Inspektdocs/openDetail", {caseid: caseid}, function(resp) {
        if (resp.status) {
            $("#myModal .modal-content").html(resp.view);
			$("#myModal").modal("show").find(".modal-dialog").css("width", "850px");
        }else{
            alert(resp.message);
        }
    }, 'json').done(function() {
        jQuery.unblockUI();
        // $("#InspectScanForm img.origimgwrapper").each(function(ele){
        //     if($(this).attr('rel-url').length!==0){
        //         loadS3Image($(this).attr('rel-url'),$(this));
        //     }
        // });
    });
    return false;
}

function loadS3Image(imageUrl,ele) {
    $.ajax({
        type: 'GET',
        url: imageUrl,
        dataType: null,
        data: null,
        xhrFields: {
            responseType: 'blob'
        },
        success: function (imageData) {
            var blobUrl = window.URL.createObjectURL(imageData);
            ele.attr('src', blobUrl);
        }
    });  
};