function checkAtomicIncome(renter){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"admin/atomic/atomic_incomes/income",{user:renter},function (data) {
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
        let returnstr='';
        $.each(resp,function(i,val){
            if(!val.error){
                console.log(val.income.company.branding.logo);
                returnstr +='<div class="row"><fieldset class="col-md-6">\
                        <legend class="text-semibold">Income Details :</legend>\
                        <div class="row">\
                            <label class="col-lg-4 control-label">Employer Name :</label>\
                            <div class="col-lg-4 control-label text-bold">'+val.income.company.name+'</div><div class="col-lg-4 bg-grey">';
                            /*if(typeof val.income.company.branding !==null && typeof val.income.company.branding.logo!=null){
                                returnstr +='<img src="'+val.income.company.branding.logo.url+'" width="50"/>';
                            }*/
                returnstr +='</div></div><div class="row">\
                            <label class="col-lg-4 control-label">Annual Income :</label>\
                            <div class="col-lg-8 control-label text-bold">'+val.income.income.annualIncome+'</div>'+
                        '</div>\
                        <div class="row">\
                            <label class="col-lg-4 control-label">Hourly Income :</label>\
                            <div class="col-lg-8 control-label text-bold">'+val.income.income.hourlyIncome+'</div>'+
                        '</div>\
                        <div class="row">\
                        <label class="col-lg-4 control-label">Income Type:</label>\
                        <div class="col-lg-8 control-label text-bold">'+val.income.income.incomeType+'</div>'+
                        '</div>\
                        <div class="row">\
                            <label class="col-lg-4 control-label">Net Hourly Rate :</label>\
                            <div class="col-lg-8 control-label text-bold">'+val.income.income.netHourlyRate+'</div>'+
                        '</div>\
                        <div class="row">\
                            <label class="col-lg-4 control-label">Pay Cycle :</label>\
                            <div class="col-lg-4 control-label text-bold">'+val.income.income.payCycle+'</div>'+
                        '</div>\
                        <div class="row">\
                            <label class="col-lg-4 control-label">Next Expected Pay Date :</label>\
                            <div class="col-lg-8 control-label text-bold">'+val.income.income.nextExpectedPayDate+'</div>'+
                        '</div>\
                        <div class="row">\
                            <div class="col-lg-4"><button type="button" class="btn btn-primary" onclick="pullatomicstatement('+val.data.id+')">Pull Income Statement</button></div>'+
                        '</div>\
                    </fieldset>\
                    <fieldset class="col-md-6">\
                        <div class="row">\
                            <legend class="text-semibold">Employer Details :</legend>\
                            \<div class="col-lg-4"><button type="button" class="btn btn-primary" onclick="pullEmployer('+val.data.id+')">Pull Employer</button></div>'+
                            '<div class="col-lg-12" id="employer_'+val.data.id+'"></div>'+
                        '</div>\
                        <div class="row">\
                            <legend class="text-semibold">Employee Details :</legend>\
                            <div class="col-lg-4"><button type="button" class="btn btn-primary" onclick="pullEmployeIdentity('+val.data.id+')">Pull Identity</button></div>'+
                            '<div class="col-lg-12" id="employee_'+val.data.id+'"></div>'+
                        '</div>\
                    </fieldset>\
                    </div>';
            }else{
                returnstr +='<fieldset class="col-md-12">\
                        <legend class="text-semibold">Income Details :</legend>\
                        <div class="row">\
                            <label class="col-lg-4 control-label">Employer Name :</label>\
                            <div class="col-lg-8 control-label text-bold">'+val.data.company+'</div>'
                        +'</div>\
                        <div class="row">\
                            <label class="col-lg-4 control-label">Error :</label>\
                            <div class="col-lg-8 control-label text-bold">'+val.error+'</div>'+
                        '</div>\
                    </fieldset>';
            }
        });
        return returnstr;
}

function pullEmployer(recordid){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"admin/atomic/atomic_incomes/pullEmployer",{recordid:recordid},function (data) {
        if(!data.status){
            alert(data.message);
            return;
        }
        var _return='<div class="row">\
                <label class="col-lg-4 control-label">Employer Name :</label>\
                <div class="col-lg-8 control-label text-bold">'+data.result.data[0].employment.employer.name+'</div>'+
            '</div><div class="row">\
            <label class="col-lg-4 control-label">Employee Type :</label>\
            <div class="col-lg-8 control-label text-bold">'+data.result.data[0].employment.employeeType+'</div>'+
            '</div><div class="row">\
            <label class="col-lg-4 control-label">Job Title :</label>\
            <div class="col-lg-8 control-label text-bold">'+data.result.data[0].employment.jobTitle+'</div>'+
                '</div><div class="row">\
                <label class="col-lg-4 control-label">Job Date :</label>\
                <div class="col-lg-8 control-label text-bold">'+data.result.data[0].employment.startDate+'</div>'+
            '</div>';
        $("#employer_"+recordid).html(_return);
    },'json').done(function(){
        jQuery.unblockUI();
    });
}

function pullEmployeIdentity(recordid){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"admin/atomic/atomic_incomes/pullEmployeIdentity",{recordid:recordid},function (data) {
        if(!data.status){
            alert(data.message);
            return;
        }
        var _return='<div class="row">\
                <label class="col-lg-4 control-label">Employee Name :</label>\
                <div class="col-lg-8 control-label text-bold">'+data.result.data[0].identity.firstName +' '+data.result.data[0].identity.lastName+'</div>'+
            '</div><div class="row">\
            <label class="col-lg-4 control-label">Email :</label>\
            <div class="col-lg-8 control-label text-bold">'+data.result.data[0].identity.email+'</div>'+
            '</div><div class="row">\
            <label class="col-lg-4 control-label">Phone :</label>\
            <div class="col-lg-8 control-label text-bold">'+data.result.data[0].identity.phone+'</div>'+
                '</div><div class="row">\
                <label class="col-lg-4 control-label">Address :</label>\
                <div class="col-lg-8 control-label text-bold">'+data.result.data[0].identity.address+' '+data.result.data[0].identity.city+' '+data.result.data[0].identity.state+' '+data.result.data[0].identity.postalCode+'</div>'+
            '</div>';
        $("#employee_"+recordid).html(_return);
        
    },'json').done(function(){
        jQuery.unblockUI();
    });
}

function pullatomicstatement(id){
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
        css: {'z-index': '9999'}
    });
    $.post(SITE_URL+"admin/atomic/atomic_incomes/statement",{id:id},function (resp) {
        if(resp.status){
            var html='<div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button></div>';
            html +='<div class="modal-body"><div class="row">\
                        <label class="col-lg-4 control-label"></label>\
                        <div class="col-lg-4 control-label text-bold">'+resp.result.company.name+'</div>'
                        +'<div class="col-lg-4 bg-grey">';
                        /*if( resp.result.company.branding.logo.url !==null){
                            html +='<img src="'+resp.result.company.branding.logo.url+'" width="50"/>';
                        }*/
                        
            html +='</div></div>';
            html +=''+parseStatementViewData(resp.result.statements)+'</div>';
            html +='<div class="modal-footer"><button type="button" class="btn btn-primary mt-10" data-dismiss="modal">Close</button></div>';
        
            $("#statementModal .modal-content").html(html);
            $("#statementModal").modal('show').find('.modal-dialog').css('width', '650px');
        }else{
            alert(resp.message);
        }
        
    },'json').done(function(){
        jQuery.unblockUI();
        initcollapse();
    });
    
}

function parseStatementViewData(resp){
    let returnstr='';
    let dateFormatoptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    $.each(resp,function(i,val){
        returnstr +='<fieldset class="col-md-12">\
                    <div class="panel panel-flat panel-collapsed">\
                        <div class="panel-heading">\
                            <h6 class="panel-title">'+
                            ( new Date(val.date).toLocaleDateString(undefined, dateFormatoptions)) +'  : Net Amount :$'+val.netAmount+'</h6>\
                            <div class="heading-elements">\
                                <ul class="icons-list">\
                                    <li><a data-action="collapse" class="rotate-180"></a></li>\
                                </ul>\
                            </div>\
                        <a class="heading-elements-toggle"><i class="icon-menu"></i></a></div>\
                        <div class="panel-body" style="display: none;">\
                            <legend class="text-semibold">Earnings :</legend>\
                            '+parselistViewData(val.earnings)+'\
                            <legend class="text-semibold">Deductions :</legend>\
                            '+parselistViewData(val.deductions)+'\
                        </div>\
                    </div>\
                </fieldset>';
        
        
    });
    return returnstr;
}

function parselistViewData(resp){
    let returnstr='';
    $.each(resp,function(i,val){
        returnstr +='<p>'+val.rawLabel+' : '+val.amount+'</p>';
    });
    return returnstr;
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