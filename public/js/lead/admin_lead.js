 /**Filter out user from leads page**/
    function refreshLead(leadid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Sending...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"admin/lead/leads/refreshlead", {leadid:leadid},function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width', '650px');

        });
    }
     /**associated user from leads page**/
    function associateLead(leadid,userid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Sending...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"admin/lead/leads/associatelead", {leadid:leadid,userid:userid},function (data) {
            jQuery.unblockUI();
            alert(data.message);
            if(data.status){
                location.reload();
            }
        },'json');
    }