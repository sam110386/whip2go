    function startBooking(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Sending...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_bookings/startBooking", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            if(data.status){
                swal({
                    title: data.message,
                    text: "I will close in 2 seconds.",
                    confirmButtonColor: "#2196F3",
                    timer: 2000
                });
                $("#update_log table").find("tr#tripRow"+data.orderid).load(SITE_URL+"cloud/linked_bookings/load_single_row", {'orderid':orderid});
            }else{
                alert(data.message);
            }
        },'json');
    }
    function cancelBooking(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_bookings/loadcancelBooking", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show');
        });
    }
    function processCancel(btn){
        $(btn).prop('disabled',true);
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Sending...</h1>', 
            css:{'z-index':'9999'}
        });
        var params=$("#cancelForm").serialize();
        $.post(SITE_URL+"cloud/linked_bookings/cancelBooking", params,function (data) {
            jQuery.unblockUI();
            if(data.status){
                $("#myModal").modal('hide');
                swal({
                    title: data.message,
                    text: "I will close in 2 seconds.",
                    confirmButtonColor: "#2196F3",
                    timer: 2000
                });
                $("#update_log table").find("tr#tripRow"+data.orderid).hide();
            }else{
                alert(data.message);
            }

        },'json');
    }
    function completeBooking(orderid,autorenew=0){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_bookings/loadcompleteBooking", {'orderid':orderid,autorenew:autorenew},function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            if(!autorenew){
                $("#myModal").modal('show');
            }
        });
    }
    function processComplete(btn){
        $(btn).prop('disabled',true);
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Sending...</h1>', 
            css:{'z-index':'9999'}
        });
        var params=$("#completeForm").serialize();
        $.post(SITE_URL+"cloud/linked_bookings/completeBooking", params,function (data) {
            jQuery.unblockUI();
            if(data.status){
                $("#myModal").modal('hide');
                swal({
                    title: data.message,
                    text: "I will close in 2 seconds.",
                    confirmButtonColor: "#2196F3",
                    timer: 2000
                });
                $("#update_log table").find("tr#tripRow"+data.orderid).hide();
            }else{
                alert(data.message);
            }

        },'json');
    }
    
    function downloadBookingDoc(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_bookings/getinsurancepopup", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width','650px');;
        });
    }
    
    function getinsurancedoc(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_bookings/getinsurancetoken", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            if(!data.status){
                alert(data.message);
            }else{
                window.open(data.result.file);
            }
        });
    }
    /**to get vehicle registration doc***/
    function getVehicleRegistration(vehicleid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_vehicles/getVehicleRegistration", {'vehicleid':vehicleid},function (data) {
            jQuery.unblockUI();
            if(!data.status){
                alert(data.message);
            }else{
                window.open(data.result.file);
            }
        });
    }
    /*
    function getmessagehistory(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"admin/message_histories/loadmessagehistory", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show');
        });
    }*/
    /*retry insurance fee payemnt*/
    function retryinsurancefee(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_bookings/retryinsurancefee", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            if(data.status=='success'){
                swal({
                    title: data.message,
                    text: "I will close in 2 seconds.",
                    confirmButtonColor: "#2196F3",
                    timer: 2000
                });
                $("#update_log table").find("tr#tripRow"+data.orderid).load(SITE_URL+"cloud/linked_bookings/load_single_row", {'orderid':orderid});
            }else{
                alert(data.message);
            }
        },'json');
    }
    /*retry initial fee payemnt*/
    function retryinitialfee(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_bookings/retryinitialfee", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            if(data.status=='success'){
                swal({
                    title: data.message,
                    text: "I will close in 2 seconds.",
                    confirmButtonColor: "#2196F3",
                    timer: 2000
                });
                $("#update_log table").find("tr#tripRow"+data.orderid).load(SITE_URL+"cloud/linked_bookings/load_single_row", {'orderid':orderid});
            }else{
                alert(data.message);
            }
        },'json');
    }
    /*retry rental fee payemnt*/
    function retryrentalfee(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_bookings/retryrentalfee", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            if(data.status=='success'){
                swal({
                    title: data.message,
                    text: "I will close in 2 seconds.",
                    confirmButtonColor: "#2196F3",
                    timer: 2000
                });
                $("#update_log table").find("tr#tripRow"+data.orderid).load(SITE_URL+"cloud/linked_bookings/load_single_row", {'orderid':orderid});
            }else{
                alert(data.message);
            }
        },'json');
    }
    /*retry rental fee payemnt*/
    function retryemf(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_bookings/retryemf", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            if(data.status=='success'){
                swal({
                    title: data.message,
                    text: "I will close in 2 seconds.",
                    confirmButtonColor: "#2196F3",
                    timer: 2000
                });
                $("#update_log table").find("tr#tripRow"+data.orderid).load(SITE_URL+"cloud/linked_bookings/load_single_row", {'orderid':orderid});
            }else{
                alert(data.message);
            }
        },'json');
    }
    /*retry deposit fee payemnt*/
    function retrydepositfee(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_bookings/retrydepositfee", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            if(data.status=='success'){
                swal({
                    title: data.message,
                    text: "I will close in 2 seconds.",
                    confirmButtonColor: "#2196F3",
                    timer: 2000
                });
                $("#update_log table").find("tr#tripRow"+data.orderid).load(SITE_URL+"cloud/linked_bookings/load_single_row", {'orderid':orderid});
            }else{
                alert(data.message);
            }
        },'json');
    }
    
    /*retry toll fee payemnt*/
    function retrytollfee(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_bookings/retrytollfee", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            if(data.status=='success'){
                swal({
                    title: data.message,
                    text: "I will close in 2 seconds.",
                    confirmButtonColor: "#2196F3",
                    timer: 2000
                });
                $("#update_log table").find("tr#tripRow"+data.orderid).load(SITE_URL+"cloud/linked_bookings/load_single_row", {'orderid':orderid});
            }else{
                alert(data.message);
            }
        },'json');
    }
    /*load booking transaction logs**/
    function gettransactionlogs(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/payment_logs/index", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width','850px');
        });
    }
    
    /***non review page function **/
    function getmessagehistory(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/message_histories/loadmessagehistory", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width','800px');;
        });
    }
    function getreviewpopup(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/booking_reviews/reviewpopup", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width','450px');;
        });
    }
    
    /******send new message***/
    /**send new message**/
    function loadnewmessgae(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/message_histories/loadnewmessage", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width','800px');
        });
    }
    
    /****send message**/
    function SendNewMessage(){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        var data=jQuery("form#newmessageform").serialize();
        $.post(SITE_URL+"cloud/message_histories/sendnewmessage", data,function (data) {
            jQuery.unblockUI();
            if(data.status){
                alert(data.message);
                $("#myModal").modal('hide');
            }else{
                alert(data.message);
            }
        },'json');
    }
    
    /****report page related functions***/
    function openTripDetails(tripId, thisObj) {
        jQuery.blockUI({ message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Just a moment...</h1>' });
        jQuery.post(SITE_URL+"cloud/linked_reports/details/" + tripId, {}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width','850px');
        });
        jQuery.unblockUI();
        return false;
    }
      //open combined booking details
    function openCombinedBookingDetails(tripId) {
        jQuery.blockUI({ message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Just a moment...</h1>' });
        jQuery.post(SITE_URL+"cloud/linked_reports/autorenewddetails/" + tripId, {}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width','850px');
        });
        jQuery.unblockUI();
        return false;
    }
    function reviewimages(orderid){
        jQuery.blockUI({ message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Just a moment...</h1>' });
        jQuery.post(SITE_URL+"cloud/booking_reviews/reviewimages/" + orderid, {}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show');
        });
        jQuery.unblockUI();
        return false;
    }
    function loadsubbooking(orderid){
        var havingchild=jQuery("tr#tr_"+orderid).attr('rel-parent');
        if(havingchild=='yes'){
            jQuery("tbody tr.child_"+orderid).each(function(){
                jQuery(this).remove();
            });
            jQuery("tr#tr_"+orderid).attr('rel-parent','no');
            return false;
        }
        jQuery.blockUI({ message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Just a moment...</h1>' });
        jQuery.post(SITE_URL+"cloud/linked_reports/loadsubbooking/" + orderid, {}, function (data) {
            if(data.status=='success'){
               jQuery("tr#tr_"+data.booking_id).after(data.data); 
               jQuery("tr#tr_"+data.booking_id).attr('rel-parent','yes');
            }
            jQuery.unblockUI();
        },'json');
        
        return false;
    }
    
     /**vehicle listing page functions***/
    function loadVehicleStatus(vehicleid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_vehicles/loadVehicleStatus", {'vehicleid':vehicleid},function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width','650px');;
        });
    }
    /**save status***/
    function changeVehicleStatus(){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Sending...</h1>', 
            css:{'z-index':'9999'}
        });
        var params=$("#statusChangeForm").serialize();
        $.post(SITE_URL+"cloud/linked_vehicles/changeVehicleStatus", params,function (data) {
            jQuery.unblockUI();
            if(data.status){
                $("#myModal").modal('hide');
                $("table.vehiclelist").find("tr#"+data.vehicleid).load(SITE_URL+"cloud/linked_vehicles/loadSingleRow", {'vehicleid':data.vehicleid});
            }else{
                alert(data.message);
            }

        },'json');
    }
    /**for agreement PDF **/
    function getagreement(orderid){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_bookings/getagreement", {'orderid':orderid},function (data) {
            jQuery.unblockUI();
            if(!data.status){
                alert(data.message);
            }else{
                window.open(data.result.file);
            }
        });
    }
    
    /**for SMS logs listing page **/
    function messageDetail(id) {
        jQuery.blockUI({ message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Just a moment...</h1>' });
        jQuery.post(SITE_URL+"admin/smslogs/details/" + id, {}, function (data) {
            jQuery.unblockUI();
            jQuery.colorbox({
                width: "700px;",
                html: data
            });
        });
        jQuery.unblockUI();
        return false;
    }
    
    /**for SMS logs listing page **/
    function deleteMessage(id){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"admin/smslogs/delete/"+id, {},function (data) {
            jQuery.unblockUI();
            if(data.status=='success'){
                jQuery(".right_content table #tr_"+data.recordid).remove();
            }else{
                alert(data.message);
            }
        },'json');
    }
    
     /**vehicle passtime status update***/
    function changePasstimeVehicleStatus(vehicleid,status){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Sending...</h1>', 
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_vehicles/changePasstimeVehicleStatus",{vehicleid:vehicleid,status:status},function (data) {
            jQuery.unblockUI();
            if(data.status){
                $("#myModal").modal('hide');
                $("table.vehiclelist").find("tr#"+data.vehicleid).load(SITE_URL+"cloud/linked_vehicles/loadSingleRow", {'vehicleid':data.vehicleid});
            }else{
                alert(data.message);
            }

        },'json');
    }
    
    /**for Change attached CC info save**/
    function changeVehicleLockTime(booking) {
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL + "cloud/linked_bookings/loadvehicleexpiretime", {"booking": booking}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width', '450px');
            $('#TextPasstimeThreshold').datetimepicker({});

        });
    }
    /**process Change attached CC info save**/
    function processVehicleLockTime(){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Sending...</h1>', 
            css:{'z-index':'9999'}
        });
        var params=$("#loadvehicleexpiretime").serialize();
        $.post(SITE_URL+"cloud/linked_bookings/processvehicleexpiretime", params,function (data) {
            jQuery.unblockUI();
            if(data.status){
                $("#myModal").modal('hide');
                swal({
                    title: data.message,
                    text: "I will close in 2 seconds.",
                    confirmButtonColor: "#2196F3",
                    timer: 2000
                });
            }else{
                alert(data.message);
            }

        },'json');
    }
    
   
    /****Update booking Payment Setting*/
    function updateOrderDepositRules(bookingid){
        if(!confirm("Are you sure you want to update payment setting for this booking?")){return false;}
        location.href=SITE_URL + "cloud/order_deposit_rules/linkedupdate/"+bookingid;
    }
    function cancelReservation(lease_id){
        swal({
            title: "",
            text: "Are you sure you want to cancel this?",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-success",
            confirmButtonText: "Yes",
            cancelButtonText: "No",
            closeOnConfirm: true,
            closeOnCancel: true
          },
          function(isConfirm) {
            if (isConfirm) {
                //create related booking
                jQuery.blockUI({
                    message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
                    css:{'z-index': '9999'}
                });
                $.post(SITE_URL + "cloud/vehicle_reservations/markBookingCancel", {"lease_id": lease_id}, function (data) {
                    jQuery.unblockUI();
                    if(data.status){
                        jQuery("#postsPaging table tbody tr#tripRow"+data.result.lease_id).remove();
                    }else{
                        swal("Error!",data.message,"error");
                    }
                }, 'json');
            }
        });
    }
    
    function createVehicleReservation(lease_id){
        jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> loading...</h1>',
                css:{'z-index':'9999'}
        });
        $.post(SITE_URL + "cloud/vehicle_reservations/createBooking", {"lease_id":lease_id}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data).show();
            $("#myModal").modal('show').find('.modal-dialog').css('width','1100px');
            initializeDate();
        });
    }
   function initializeDate(){
        $('#daterangefrom').datetimepicker({format: 'MM/DD/YYYY'});
        $('#daterangeto').datetimepicker({
            useCurrent: false, //Important! See issue #1075
            format: 'MM/DD/YYYY'
        });
        $("#daterangefrom").on("dp.change", function (e) {
            $('#daterangeto').data("DateTimePicker").minDate(e.date);
        });
        $("#daterangeto").on("dp.change", function (e) {
            $('#daterangefrom').data("DateTimePicker").maxDate(e.date);
        });
   
   } 
   function SaveBooking() {
        var pickup_address = jQuery('#TextLocation').val();
        var pickup_time = jQuery('#TextPickupTime').val();
        var lease_id=jQuery("#TextLeaseId").val();
        var errMsg = '';
        if ($.trim(pickup_address) == '') {
            errMsg += 'Please enter Pickup Address\n';
        }
        if (pickup_time == '') {
            errMsg += 'Please enter Pickup Time\n';
        }
        if (errMsg !== '') {
            alert(errMsg);
            return false;
        }
        var params=$("#triplogForm").serialize();
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> loading...</h1>',
                    css:{'z-index':'9999'}
        });
        jQuery.post(SITE_URL+"cloud/vehicle_reservations/saveVehicleBooking", params, function (data) {
            jQuery.unblockUI();
            if (data.status) {
                swal({title: data.message,text: "I will close in 2 seconds.",
                    confirmButtonColor: "#2196F3",timer: 2000
                });
                $("#myModal").modal('hide');
                $.post(SITE_URL+"cloud/vehicle_reservations/markBookingCompleted", {"lease_id":lease_id},function (data) {
                    jQuery("#postsPaging table tbody tr#tripRow"+data.lease_id).remove();
                });
            } else {
                swal("Error!",data.message,"error");
            }

        }, 'json');
        return false;
    }
    
    /**Update Vehicle GPS info**/
    function changeVehicleGps(booking) {
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL + "cloud/linked_bookings/loadvehiclegps", {"booking": booking}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width', '550px');

        });
    }
    /**process Vehicle GPS details save**/
    function processVehicleGps(sync=false){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Sending...</h1>', 
            css:{'z-index':'9999'}
        });
        var params=$("#loadvehiclegps").serialize()+'&sync=' + sync;;
        $.post(SITE_URL+"cloud/linked_bookings/updatevehiclegps", params,function (data) {
            jQuery.unblockUI();
            if(data.status){
                if(sync){
                    $("#TextPasstimeSerialno").val(data.result);
                }
                alert(data.message);
            }else{
                alert(data.message);
            }
        },'json');
    }
    /**process booking start odometer save**/
    function resetStartingMileage(){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Sending...</h1>', 
            css:{'z-index':'9999'}
        });
        var params=$("#loadvehiclegps").serialize();
        $.post(SITE_URL+"cloud/linked_bookings/updatestartodometer", params,function (data) {
            jQuery.unblockUI();
            if(data.status){
                alert(data.message);
            }else{
                alert(data.message);
            }
        },'json');
    }
    
    /***function to get user details, on pending trip listing page***/
    function getuserdetails(userid){
         jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL + "cloud/vehicle_reservations/getuserdetails", {userid: userid}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width', '550px');
            //initialize editable element
            $("#provenIncome").editable({
                success: function(response, newValue) {
                    if(response.status == 'error') return response.msg;
                }
            });
        });
    }
    /***function to get user bank statement, on pending trip listing page***/
    function getplaidrecord(userid){
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL + "cloud/vehicle_reservations/getplaidrecord", {userid: userid}, function (data) {
            jQuery.unblockUI();
            if(data.status){
                $("#plaidModal .modal-content").attr('id',"bankdetail").html(data.view);
                $("#plaidModal").modal('show').find('.modal-dialog').css('width', '650px');
                plaidtoken=data.plaidtoken;
                userid=data.userid;
                loadbankbalance(plaidtoken,userid);
            }else{
                alert(data.message);
            }
        },'json');
    }
        
    $('#plaidModal').on('show.bs.modal', function () {
        var modalParent = $(this).attr('data-modal-parent');
        $(modalParent).css('opacity', 0);
    });

    $('#plaidModal').on('hidden.bs.modal', function () {
        var modalParent = $(this).attr('data-modal-parent');
        $(modalParent).css('opacity', 1);
    });
    
    $('#statementModal').on('show.bs.modal', function () {
        var modalParent = $(this).attr('data-modal-parent');
        $(modalParent).css('opacity', 0);
    });

    $('#statementModal').on('hidden.bs.modal', function () {
        var modalParent = $(this).attr('data-modal-parent');
        $(modalParent).css('opacity', 1);
    });
    
    function loadbankbalance(plaidtoken,userid){
        $("#bankdetail .plaidbalance" ).each(function( index ) {
            var ele=$(this);
            showUIBlocker(ele.parent());
            console.log(index + ": " + ele.attr('rel-token'));
            var acccountid=ele.attr('rel-token');
            $.post(SITE_URL+"cloud/vehicle_reservations/getplaidbalance",{userid:userid,'plaid_token':plaidtoken,acccountid:acccountid},function(resp){
                ele.html(resp.balance);
                console.log( "balance: " + resp.balance);
            },'json').done(function(){
                $(ele.parent()).unblock();
            });
        });
    }
    function showUIBlocker(ele){
        $(ele).block({ 
            message: '<i class="icon-spinner4 spinner"></i>',
            overlayCSS: {
                backgroundColor: '#fff',opacity: 0.8,cursor: 'wait'
            },
            css: {border: 0,padding: 0,backgroundColor: 'transparent'}
        });
    }
    function loadbankstatement(plaidtoken,userid,acccountid){
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL+"cloud/vehicle_reservations/bankstatement",{userid:userid,'plaid_token':plaidtoken,acccountid:acccountid},function(resp){
            if(resp.status){
                $("#statementModal .modal-content").html(resp.transactions);
                $("#statementModal").modal('show').find('.modal-dialog').css('width', '650px');
            }else{
                alert(resp.message);
            }
        },'json').done(function(){
            jQuery.unblockUI();
        });
    }
    
    function CheckOdometer(vehicleid){
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL+"cloud/vehicle_reservations/checkodometer",{vehicleid:vehicleid},function(resp){
            if(resp.status==='success'){
                $("#gpstester .messagedisplay").html(resp.html);
            }else{
                alert(resp.message);
            }
        },'json').done(function(){
            jQuery.unblockUI();
        });
    }
    
    function CheckStaterInterrupt(vehicleid,orderid){
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL+"cloud/vehicle_reservations/checkStarterInterrupt",{vehicleid:vehicleid,orderid:orderid},function(resp){
            if(resp.status==='success'){
                $("#gpstester .messagedisplay").html(resp.html);
            }else{
                alert(resp.message);
            }
        },'json').done(function(){
            jQuery.unblockUI();
        });
    }
    
    
    function DisableStaterInterrupt(vehicleid,orderid){
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL+"cloud/vehicle_reservations/disableStaterInterrupt",{vehicleid:vehicleid,orderid:orderid,disable:1},function(resp){
            if(resp.status){
                $("#starterWorkswrapper .starterWorkOptions").show();
                $("#starterWorkswrapper .starterWorks").hide();
            }else{
                alert(resp.message);
            }
        },'json').done(function(){
            jQuery.unblockUI();
        });
    }
    
    function StaterInterruptWorks(vehicleid,orderid){
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL+"cloud/vehicle_reservations/staterInterruptWorks",{vehicleid:vehicleid,orderid:orderid},function(resp){
            if(resp.status==='success'){
                $("#starterWorkswrapper .starterWorks").show();
                $("#starterWorkswrapper .starterWorkOptions").hide();
            }else{
                alert(resp.message);
            }
        },'json').done(function(){
            jQuery.unblockUI();
        });
    }
    
    function EnableStaterInterrupt(vehicleid,orderid){
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL+"cloud/vehicle_reservations/disableStaterInterrupt",{vehicleid:vehicleid,orderid:orderid,disable:0},function(resp){
            if(resp.status){
                $("#starterWorkswrapper .starterEnableWorkOptions").show();
            }else{
                alert(resp.message);
            }
        },'json').done(function(){
            jQuery.unblockUI();
        });
    }
    
    /***function to get Vehicle details, on pending trip listing page***/
    function getvehicledetails(vehicleid,orderid){
         jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL + "cloud/linked_vehicles/getvehicledetails", {vehicleid: vehicleid,orderid:orderid}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width', '550px');
        });
    }
    
    
    /***function to get Vehicle details, on pending trip listing page***/
    function updateVehicleDetails(){
         jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        var data = new FormData($("#updateVehicleDetails").get(0));
        $.ajax({
            url: SITE_URL + "cloud/linked_vehicles/updateVehicleDetails",
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
    
    /***function to Change the Vehicle, on pending trip listing page***/
    function changeDatetime(orderid){
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL + "cloud/vehicle_reservations/changeDatetime", {orderid:orderid}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width', '550px');
            initializeDate();
        });
    }
    
    function updateDatetime(){
         jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        var fromdata=$("#updateVehicleDetails").serialize();
        $.post(SITE_URL + "cloud/vehicle_reservations/updateDatetime", fromdata, function (data) {
            if (!data.status) {
                alert(data.message);
            } else {
                alert(data.message);
                $("#myModal").modal('hide');
            }
        },'json').done(function () {
            jQuery.unblockUI();
        });
    }
    
    function getVehicleGps(vehicleid,type){
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> loading...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL + "cloud/vehicles/linked_vehicles", {vehicleid:vehicleid}, function (data) {
            if (!data.status) {
                alert(data.message);
            } else {
                var $elem = $("#updateVehicleDetails #"+type);
                $elem.editable('setValue', data.gps_serialno).editable('toggle');
            }
        },'json').done(function () {
            jQuery.unblockUI();
        });
    }
    
    /***function to Change the Vehicle, on pending trip listing page***/
    function changeReservationVehicle(orderid){
         jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL + "cloud/vehicle_reservations/changeVehicle", {orderid:orderid}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width', '550px');
        });
    }
    
    function updateReservationVehicle(){
         jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        var fromdata=$("#updateVehicleDetails").serialize();
        $.post(SITE_URL + "cloud/vehicle_reservations/updateReservationVehicle", fromdata, function (data) {
            if (!data.status) {
                alert(data.message);
            } else {
                alert("Updated successfully");
            }
        },'json').done(function () {
            jQuery.unblockUI();
        });
    }
    
    
    /***function to Change the Vehicle, on pending trip listing page***/
    function changeReservationStatus(orderid){
         jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL + "cloud/vehicle_reservations/changeStatus", {orderid:orderid}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width', '550px');
        });
    }
    
    //save Reservation status change Request
    function updateReservationStatus(){
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
            css: {'z-index': '9999'}
        });
        var fromdata=$("#updateVehicleStatus").serialize();
        $.post(SITE_URL + "cloud/vehicle_reservations/changeSaveStatus", fromdata, function (data) {
            if (!data.status) {
                alert(data.message);
            } else {
                alert(data.message);
                $("#myModal").modal('hide');
                $("#postsPaging table").find("tr#tripRow"+data.orderid).load(SITE_URL+"cloud/vehicle_reservations/singleload", {'orderid':data.orderid});
            }
        },'json').done(function () {
            jQuery.unblockUI();
        });
    }
    
    /***function to get the Vehicle pending status log, on pending trip listing page***/
    function vehicleReservationLog(orderid){
         jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Loading...</h1>',
            css: {'z-index': '9999'}
        });
        $.post(SITE_URL + "cloud/vehicle_reservations/vehicleReservationLog", {orderid:orderid}, function (data) {
            jQuery.unblockUI();
            if(!data.status){
                alert(data.message);return false;
            }
            $("#myModal .modal-content").html(data.view);
            $("#myModal").modal('show').find('.modal-dialog').css('width', '550px');
        },'json');
    }
    
    function cloudOpenBookingDetails(order){
        jQuery.blockUI({ message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Just a moment...</h1>' });
        jQuery.post(SITE_URL+"cloud/bookings/overdue_booking_details", {order:order}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show').find('.modal-dialog').css('width','850px');
        });
        jQuery.unblockUI();
        return false;
    }