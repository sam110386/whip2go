/**
Address editable input.
Internally value stored as {city: "Moscow", street: "Lenina", building: "15"}

@class address
@extends abstractinput
@final
@example
<a href="#" id="address" data-type="address" data-pk="1">awesome</a>
<script>
$(function(){
    $('#address').editable({
        url: '/post',
        title: 'Enter city, street and building #',
        value: {
            city: "Moscow", 
            street: "Lenina", 
            building: "15"
        }
    });
});
</script>
**/
(function ($) {
    "use strict";
    
    var ClueReport = function (options) {
        this.init('cluereport', options, ClueReport.defaults);
    };

    //inherit from Abstract input
    $.fn.editableutils.inherit(ClueReport, $.fn.editabletypes.abstractinput);

    $.extend(ClueReport.prototype, {
        /**
        Renders input from tpl

        @method render() 
        **/        
        render: function() {
           this.$input = this.$tpl.find('input');
        },
        
        /**
        Default method to show value in element. Can be overwritten by display option.
        
        @method value2html(value, element) 
        **/
        value2html: function(value, element) {
            if(!value) {
                $(element).empty();
                return; 
            }
            var html = $('<div>').text(value.accidents_3).html() + ', ' + $('<div>').text(value.accidents_5).html() + ' st., bld. ' + $('<div>').text(value.violations).html();
            $(element).html(html); 
        },
        
        /**
        Gets value from element's html
        
        @method html2value(html) 
        **/        
        html2value: function(html) {        
          /*
            you may write parsing method to get value by element's html
            e.g. "Moscow, st. Lenina, bld. 15" => {city: "Moscow", street: "Lenina", building: "15"}
            but for complex structures it's not recommended.
            Better set value directly via javascript, e.g. 
            editable({
                value: {
                    city: "Moscow", 
                    street: "Lenina", 
                    building: "15"
                }
            });
          */ 
          return null;  
        },
      
       /**
        Converts value to string. 
        It is used in internal comparing (not for sending to server).
        
        @method value2str(value)  
       **/
       value2str: function(value) {
           var str = '';
           if(value) {
               for(var k in value) {
                   str = str + k + ':' + value[k] + ';';  
               }
           }
           return str;
       }, 
       
       /*
        Converts string to value. Used for reading value from 'data-value' attribute.
        
        @method str2value(str)  
       */
       str2value: function(str) {
           /*
           this is mainly for parsing value defined in data-value attribute. 
           If you will always set value by javascript, no need to overwrite it
           */
           return str;
       },                
       
       /**
        Sets value of input.
        
        @method value2input(value) 
        @param {mixed} value
       **/         
       value2input: function(value) {
           if(!value) {
             return;
           }
           this.$input.filter('[name="accidents_3"]').val(value.accidents_3);
           this.$input.filter('[name="accidents_5"]').val(value.accidents_5);
           this.$input.filter('[name="violations"]').val(value.violations);
           this.$input.filter('[name="notes"]').val(value.notes);
       },       
       
       /**
        Returns value of input.
        
        @method input2value() 
       **/          
       input2value: function() { 
           return {
            accidents_3: this.$input.filter('[name="accidents_3"]').val(), 
            accidents_5: this.$input.filter('[name="accidents_5"]').val(), 
            violations: this.$input.filter('[name="violations"]').val(),
            notes: this.$input.filter('[name="notes"]').val()
           };
       },        
       
        /**
        Activates input: sets focus on the first field.
        
        @method activate() 
       **/        
       activate: function() {
            this.$input.filter('[name="accidents_3"]').focus();
       },  
       
       /**
        Attaches handler to submit form in case of 'showbuttons=false' mode
        
        @method autosubmit() 
       **/       
       autosubmit: function() {
           this.$input.keydown(function (e) {
                if (e.which === 13) {
                    $(this).closest('form').submit();
                }
           });
       }       
    });

    ClueReport.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        tpl: '<div class="editable-address form-group"><label class="col-lg-8 control-label">How many accidents in the last 3 years: </label><div class="col-lg-4"><input type="number" name="accidents_3" class="form-control"/></div></div>'+
             '<div class="editable-address form-group"><label class="col-lg-8 control-label">How many accidents in the last 5 years: </label><div class="col-lg-4"><input type="number" name="accidents_5" class="form-control"/></div></div>'+
             '<div class="editable-address form-group"><label class="col-lg-8 control-label">How many moving violations in the last 4 years?</label><div class="col-lg-4"><input type="number" name="violations" class="form-control"/></div></div>'+
             '<div class="editable-address form-group"><label class="col-lg-2 control-label">Any Notes</label><div class="col-lg-10"><input type="text" name="notes" class="form-control"/></div></div>',
             
        inputclass: ''
    });

    $.fn.editabletypes.cluereport = ClueReport;

}(window.jQuery));