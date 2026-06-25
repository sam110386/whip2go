var validate = {
    ignore: 'input[type=hidden], .select2-search__field', // ignore hidden fields
    errorClass: 'validation-error-label',
    successClass: 'validation-valid-label',
    highlight: function(element, errorClass) {
        $(element).removeClass(errorClass);
    },
    unhighlight: function(element, errorClass) {
        $(element).removeClass(errorClass);
    },

    // Different components require proper error label placement
    errorPlacement: function(error, element) {

        // Styled checkboxes, radios, bootstrap switch
        if (element.parents('div').hasClass("checker") || element.parents('div').hasClass("choice") || element.parent().hasClass('bootstrap-switch-container') ) {
            if(element.parents('label').hasClass('checkbox-inline') || element.parents('label').hasClass('radio-inline')) {
                error.appendTo( element.parent().parent().parent().parent() );
            }
             else {
                error.appendTo( element.parent().parent().parent().parent().parent() );
            }
        }

        // Unstyled checkboxes, radios
        else if (element.parents('div').hasClass('checkbox') || element.parents('div').hasClass('radio')) {
            error.appendTo( element.parent().parent().parent() );
        }

        // Input with icons and Select2
        else if (element.parents('div').hasClass('has-feedback') || element.hasClass('select2-hidden-accessible')) {
            error.appendTo( element.parent() );
        }

        // Inline checkboxes, radios
        else if (element.parents('label').hasClass('checkbox-inline') || element.parents('label').hasClass('radio-inline')) {
            error.appendTo( element.parent().parent() );
        }

        // Input group, styled file input
        else if (element.parent().hasClass('uploader') || element.parents().hasClass('input-group')) {
            error.appendTo( element.parent().parent() );
        }

        else {
            error.insertAfter(element);
        }
    },
    rules: {
        email: {
            email: true
        }
    }
}
$(function() {
    $("#ssnhideview").click(function() {
        $('#ElandSsn').attr('type', function(index, attr) {
            return attr == 'text' ? 'password' : 'text';
        });
        $('#ssnhideviewicon').toggleClass("icon-eye2 icon-eye-blocked2");

    });
    $("#stepyvalidation,#finalstep").validate(validate);
});
var savestepone=function(){
    if(!$("#stepyvalidation").valid()){
        return false;
    }
    var params=$("#stepyvalidation").serialize();
    $.post("<?php echo SITE_URL?>eland/elandmob/saveStepOne/",params,function(resp){

    },'json');
}

$(function(){
    $('.stepy-finish').click(function(){
        if(!$("#finalstep").valid()){
            return false;
        }
        
       
        $(this).prop('disabled',true);
        $("#finalstep").submit();
        return true;
    });
});