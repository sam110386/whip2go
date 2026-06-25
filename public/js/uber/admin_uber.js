function format(item) {
    return item.tag;
}
jQuery(function () {
    jQuery("#RideCsOrderId").select2({
        data: {results: {}, text: 'tag'},
        formatSelection: format,
        formatResult: format,
        placeholder: "Select Booking ",
        minimumInputLength: 4,
        ajax: {
            url: SITE_URL+"admin/uber/booking/bookingautocomplete",
            dataType: "json",
            type: "GET",
            data: function (params) {
                var queryParameters = {
                    term: params
                }
                return queryParameters;
            },
            processResults: function (data) {
                return {
                    results: jQuery.map(data, function (item) {
                        return {
                            tag: item.tag,
                            id: item.id,
                            name: item.name,
                            email: item.email,
                            phone: item.phone,
                            address: item.address,
                            datetime:item.datetime,
                            renter_id:item.renter_id
                        }
                    })
                };
            }
        }
    });
    jQuery("#RideCsOrderId").on('select2-selecting', function (e) {
        jQuery("#RideFname").val(e.choice.name);
        jQuery("#RideAddress").html(e.choice.address);
        jQuery("#RideEmail").val(e.choice.email);
        jQuery("#RideRenterId").val(e.choice.renter_id);
        jQuery("#RidePhone").val(e.choice.phone);
        jQuery('#RidePickupTime').val(e.choice.datetime);
    });
    //pending booking autocomplete
    jQuery("#RideReservationId").select2({
        data: {results: {}, text: 'tag'},
        formatSelection: format,
        formatResult: format,
        placeholder: "Select Booking ",
        minimumInputLength: 2,
        ajax: {
            url: SITE_URL+"admin/uber/booking/reservationautocomplete",
            dataType: "json",
            type: "GET",
            data: function (params) {
                var queryParameters = {
                    term: params
                }
                return queryParameters;
            },
            processResults: function (data) {
                return {
                    results: jQuery.map(data, function (item) {
                        return {
                            tag: item.tag,
                            id: item.id,
                            name: item.name,
                            email: item.email,
                            phone: item.phone,
                            address: item.address,
                            datetime:item.datetime,
                            renter_id:item.renter_id
                        }
                    })
                };
            }
        }
    });
    jQuery("#RideReservationId").on('select2-selecting', function (e) {
        jQuery("#RideFname").val(e.choice.name);
        jQuery("#RideAddress").html(e.choice.address);
        jQuery("#RideEmail").val(e.choice.email);
        jQuery("#RideRenterId").val(e.choice.renter_id);
        jQuery("#RidePhone").val(e.choice.phone);
        jQuery('#RidePickupTime').val(e.choice.datetime);
    });

});

//-------------//
jQuery(function () {
    jQuery("#RideBooking").validate();
    jQuery('#dispatchBtn').click(function () {
        if(jQuery("#RideBooking").valid()){
            jQuery.blockUI({
                message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Processing...</h1>', 
                css:{'z-index':'9999'}
            });
            var params = jQuery("#RideBooking").serialize();
            let cab=$('input[name="data[Ride][cabtype]"]:checked').val();
            let productid=$('input[name="data[Ride][cabtype]"]:checked').attr('rel-pid');
            let fareid=$('input[name="data[Ride][cabtype]"]:checked').attr('rel-fid');
            params=params+ "&" + $.param({productid:productid,fareid:fareid});
            console.log(cab+'=='+productid+'=='+fareid);
            jQuery.post(SITE_URL+"admin/uber/booking/book", params, function (data) {
                if (data.status) {
                    swal({
                        title: data.message,
                        text: "I will close in 2 seconds.",
                        confirmButtonColor: "#2196F3",
                        timer: 2000
                    });
                    //reset FORM
                    jQuery(':input','#RideBooking')
                        .not(':button, :submit, :reset')
                        .val('')
                        .prop('checked', false)
                        .prop('selected', false);
                    jQuery("#RideUserId").select2("val", ""); 
                    jQuery("#availablecars").html('');
                    jQuery(".carblock.btn").removeClass('show').addClass('hide');

                } else {
                    alert(data.message);
                }
            }, 'json').done(function(){
                jQuery.unblockUI();
            });
        }
        return false;
     });
});

//get Uber Cars
function UberCars(){
    if(jQuery("#RideBooking").valid()){
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> Requesting...</h1>', 
            css:{'z-index':'9999'}
        });
        var params = jQuery("#RideBooking").serialize();
        jQuery.post(SITE_URL+"admin/uber/booking/getUberCars", params, function (data) {
            if (data.status) {
                let vehicles='';
                $.each(data.products, function(i,product){
                    vehicles +='<li class="col-md-12"><span class="col-md-3"><img src="'+product.product.image+'"/ style="max-width:80px;"></span><span class="col-md-3">'+product.product.display_name+'</span><span class="col-md-3">'+product.estimate_info.fare.display+'</span><span class="col-md-3"><input type="radio" value="'+product.estimate_info.fare.display+'" name="data[Ride][cabtype]" class="md-radiobtn" rel-pid="'+product.product.product_id+'" rel-fid="'+product.estimate_info.fare_id+'"></span></li>';
                });
                $("#availablecars").html(vehicles);
                $(".carblock.btn").removeClass('hide').addClass('show');
            } else {
                alert(data.message);
            }
        }, 'json').done(function(){
            jQuery.unblockUI();

            $("#availablecars li").click(function(){
                $(this).find("input[type=radio]").prop("checked", true);;
            });
        });
    }
    return false;
}