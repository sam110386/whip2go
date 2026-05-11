function loadPlaidBalance(id,token,myModal='myModal'){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"cloud/plaid_users/balance", {'id':id,token:token},function (data) {
        jQuery.unblockUI();
        $("#"+myModal+" .modal-content").html(data);
        $("#"+myModal).modal('show');
    });
}

function loadPlaidBankStatement(id,token,myModal='myModal'){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"cloud/plaid_users/transactions", {'id':id,token:token},function (data) {
        jQuery.unblockUI();
        $("#"+myModal+" .modal-content").html(data);
        $("#"+myModal).modal('show').find('.modal-dialog').css('width','90%')
    });
}

function loadPlaidBankIncome(id,token,myModal='myModal'){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"cloud/plaid_users/income", {'id':id,token:token},function (data) {
        jQuery.unblockUI();
        $("#"+myModal+" .modal-content").html(data);
        $("#"+myModal).modal('show').find('.modal-dialog').css('width','90%');
    });
}

function loadPlaidBankDetail(id,accountid,myModal='myModal'){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"cloud/plaid_users/income", {'id':id,accountid:accountid},function (data) {
        jQuery.unblockUI();
        $("#"+myModal+" .modal-content").html(data);
        $("#"+myModal).modal('show').find('.modal-dialog').css('width','90%');
    });
}
function PullPlaidPayStubData(userid,usertoken,myModal='myModal'){
    jQuery.blockUI({
        message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>', 
        css:{'z-index':'9999'}
    });
    $.post(SITE_URL+"cloud/plaid_users/payrollincome", {'userid':userid,usertoken:usertoken},function (data) {
        jQuery.unblockUI();
        $("#"+myModal+" .modal-content").html(data);
        $("#"+myModal).modal('show').find('.modal-dialog').css('width','90%');
    });
}


