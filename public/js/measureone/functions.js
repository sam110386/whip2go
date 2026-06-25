function checkMeasureOneIncome(renter){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"admin/measureone/incomes/income",{user:renter},function (data) {
        if(!data.status){
            alert(data.message);
            return;
        }
        var html='<div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button></div>';
        html +='<div class="modal-body">'+parseincomeViewData(data.result)+'</div>';
        html +='<div class="modal-footer"><button type="button" class="btn btn-primary mt-10" data-dismiss="modal">Close</button></div>';
        $("#plaidModal .modal-content").html(html);
        $("#plaidModal").modal('show').find('.modal-dialog').css('width', '850px');;
    },'json').success(function(data){
        
    }).done(function(){
        jQuery.unblockUI();
    });
}

function parseincomeViewData(resp){
        let dateFormatoptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        let returnstr ='<div class="row">\
        <legend class="text-semibold">Connected Account Details :</legend>';
        returnstr +='<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">\
                    <thead>\
                        <tr>\
                        <th align="center" class="text-center">Employer</th>\
                        <th align="center" class="text-center">Connected On</th>\
                        <th align="center" class="text-center">Source</th>\
                        <th align="center" class="text-center">Status</th>\
                        <th align="center" class="text-center">';
            returnstr +='Action</td></tr></thead><tbody>';
        $.each(resp,function(i,val){
            returnstr +='<tr>\
                        <td align="center" class="text-center">'+val.MeasureOne.datasource_name+'</td>\
                        <td align="center" class="text-center">'+( new Date(val.MeasureOne.created).toLocaleDateString(undefined, dateFormatoptions))+'</td>\
                        <td align="center" class="text-center">'+(val.MeasureOne.paystub==1?'Paystub':'Bank')+'</td>\
                        <td align="center" class="text-center">'+(val.MeasureOne.status==0?'Not Requested Yet':(val.MeasureOne.status==1?'In Progress':(val.MeasureOne.status==2?'Report Available':'Failed')))+'</td>\
                        <td align="center" class="text-center">';
            returnstr +='<button type="button" class="btn btn-primary" onclick="pullMeasureOneIncomeDetails('+val.MeasureOne.id+')">Pull Details</button></td>'+
                        '</tr>';
            
        });
        returnstr +='</tbody></table></div>';
        return returnstr;
}

function pullMeasureOneIncomeDetails(recordid){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"admin/measureone/incomes/pullMeasureOneIncomeDetails",{recordid:recordid},function (data) {
        if(!data.status){
            alert(data.message);
            return;
        }
        $("#statementModal .modal-content").html(data.view);
        $("#statementModal").modal('show').find('.modal-dialog').css('width', '850px');
       
    },'json').done(function(){
        jQuery.unblockUI();
    });
}

function containerHeight() {
    var availableHeight = $(window).height() - $('body > .navbar').outerHeight() - $('body > .navbar-fixed-top:not(.navbar)').outerHeight() - $('body > .navbar-fixed-bottom:not(.navbar)').outerHeight() - $('body > .navbar + .navbar').outerHeight() - $('body > .navbar + .navbar-collapse').outerHeight();

    $('.page-container').attr('style', 'min-height:' + availableHeight + 'px');
}
function initcollapse(){
    // Hide if collapsed by default
    $('.panel-collapsed').children('.panel-heading').nextAll().hide();

    // Rotate icon if collapsed by default
    $('.panel-collapsed').find('[data-action=collapse]').children('i').addClass('rotate-180');
    // Collapse on click
    $('.panel [data-action=collapse]').click(function (e) {
        e.preventDefault();
        var $panelCollapse = $(this).parent().parent().parent().parent().nextAll();
        $(this).parents('.panel').toggleClass('panel-collapsed');
        $(this).toggleClass('rotate-180');
        containerHeight(); // recalculate page height
        $panelCollapse.slideToggle(150);
    });
}