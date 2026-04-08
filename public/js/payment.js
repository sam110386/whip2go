/* 
this file is using to create CC token from registration page
 */
var $form = $('#savemyccdetails');
$form.find('.subscribe').on('click', payWithStripe);

/* If you're using Stripe for payments */
function payWithStripe(e) {
    e.preventDefault();
    if (!validator.form()) {
        return;
    }
    var formData=$form.serialize();
    $form.find('.subscribe').html('Processing <i class="fa fa-spinner fa-pulse"></i>').prop('disabled', true);
     $.post(SITE_URL+'logins/addmyccinfo',formData,function(data){
         if(data.status){
             $form.find('.subscribe').html('Payment successful <i class="fa fa-check"></i>');
         }else{
            $form.find('.subscribe').html('There was a problem').removeClass('success').addClass('error');
            $form.find('.payment-errors').text(data.message);
            $form.find('.payment-errors').closest('.row').show();
         }
     },'json')
    // Assign handlers immediately after making the request,
    .done(function(data, textStatus, jqXHR) {
        $form.find('.subscribe').html('Payment successful <i class="fa fa-check"></i>');
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        $form.find('.subscribe').html('There was a problem').removeClass('success').addClass('error');
        $form.find('.payment-errors').text('Try refreshing the page and trying again.');
        $form.find('.payment-errors').closest('.row').show();
    });
    /*var PublishableKey = 'pk_test_0IkTb6Qrp4I1LcQVmioLoboj'; // Replace with your API publishable key
    Stripe.setPublishableKey(PublishableKey);
    
    
    var expiry = $form.find('[name=cardExpiry]').payment('cardExpiryVal');
    var ccData = {
        number: $form.find('[name=cardNumber]').val().replace(/\s/g,''),
        cvc: $form.find('[name=cardCVC]').val(),
        exp_month: expiry.month, 
        exp_year: expiry.year
    };
    
    Stripe.card.createToken(ccData, function stripeResponseHandler(status, response) {
        if (response.error) {
            $form.find('.subscribe').html('Try again').prop('disabled', false);
            $form.find('.payment-errors').text(response.error.message);
            $form.find('.payment-errors').closest('.row').show();
        } else {
            
            $form.find('.subscribe').html('Processing <i class="fa fa-spinner fa-pulse"></i>');
            $form.find('.payment-errors').closest('.row').hide();
            $form.find('.payment-errors').text("");
            // response contains id and card, which contains additional card details            
            console.log(response.id);
            console.log(response.card);
            var token = response.id;
            alert(token);
            // AJAX - you would send 'token' to your server here.
           
        }
    });*/
}
/* Fancy restrictive input formatting via jQuery.payment library*/
$('input[name=cardNumber]').payment('formatCardNumber');
$('input[name=cardCVC]').payment('formatCardCVC');
$('input[name=cardExpiry').payment('formatCardExpiry');

/* Form validation using Stripe client-side validation helpers */
jQuery.validator.addMethod("cardNumber", function(value, element) {
    return this.optional(element) || Stripe.card.validateCardNumber(value);
}, "Please specify a valid credit card number.");

jQuery.validator.addMethod("cardExpiry", function(value, element) {    
    /* Parsing month/year uses jQuery.payment library */
    value = $.payment.cardExpiryVal(value);
    return this.optional(element) || Stripe.card.validateExpiry(value.month, value.year);
}, "Invalid expiration date.");

jQuery.validator.addMethod("cardCVC", function(value, element) {
    return this.optional(element) || Stripe.card.validateCVC(value);
}, "Invalid CVC.");
jQuery(document).ready(function(){
validator = $form.validate({
    rules: {
        cardNumber: {
            required: true,
            cardNumber: true            
        },
        cardExpiry: {
            required: true,
            cardExpiry: true
        },
        cardCVC: {
            required: true,
            cardCVC: true
        }
    },
    highlight: function(element) {
        $(element).closest('.form-control').removeClass('success').addClass('error');
    },
    unhighlight: function(element) {
        $(element).closest('.form-control').removeClass('error').addClass('success');
    },
    errorPlacement: function(error, element) {
        $(element).closest('.form-group').append(error);
    }
});

$form.find('.subscribe').prop('disabled', true);
var readyInterval = setInterval(function() {
    if (paymentFormReady()) {
        $form.find('.subscribe').prop('disabled', false);
        clearInterval(readyInterval);
    }
}, 250);
});

paymentFormReady = function() {
    if ($form.find('[name=cardNumber]').hasClass("success") &&
        $form.find('[name=cardExpiry]').hasClass("success") &&
        $form.find('[name=cardCVC]').val().length > 1) {
        return true;
    } else {
        return false;
    }
}



/****I m dealer form **/
$("#business_type").change(function(){
    if($(this).val()=='individual'){
        $("#ssn_noblk").show();
        $("#ein_noblk").hide();
    }else{
        $("#ssn_noblk").hide();
        $("#ein_noblk").show();
    }
});

$(document).ready(function(){
    var $fromsec=$("#connectme");
    $fromsec.find('.readyforconnect').prop('disabled', true);
    var readyIntervalsec = setInterval(function() {
        if ($fromsec.valid()) {
            if($fromsec.find("#ssn").val().length>0 || $fromsec.find("#ein").val().length>0){
                $fromsec.find('.readyforconnect').prop('disabled', false);
                clearInterval(readyIntervalsec);
            }
        }
    }, 550);
    
    $(".readyforconnect").click(function(){
        if (!$fromsec.valid()) {
            return;
        }
        var formData=$fromsec.serialize();
        $fromsec.find('.readyforconnect').html('Processing <i class="fa fa-spinner fa-pulse"></i>').prop('disabled', true);
         $.post(SITE_URL+'logins/getmystripeurl',formData,function(data){
             if(data.status){
                 $fromsec.find('.readyforconnect').html('You will be redirected to Stripe portal shortly <i class="fa fa-check"></i>');
                 document.location.href=data.result.url;
            }else{
                $fromsec.find('.readyforconnect').html('There was a problem').removeClass('success').addClass('error');
                
            }
         },'json')
        // Assign handlers immediately after making the request,
        .done(function(data, textStatus, jqXHR) {
            $fromsec.find('.readyforconnect').html('You will be redirected to Stripe portal shortly <i class="fa fa-check"></i>');
            //document.location.href=SITE_URL+'logins/index';
        });
    });
});


