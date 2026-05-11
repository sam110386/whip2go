var plaidtoken;

    function showUIBlocker(ele) {
        $(ele).block({
            message: '<i class="icon-spinner4 spinner"></i>',
            overlayCSS: {
                backgroundColor: '#fff',
                opacity: 0.8,
                cursor: 'wait'
            },
            css: {
                border: 0,
                padding: 0,
                backgroundColor: 'transparent'
            }
        });
    }

    
    function loanpullPlaidBank(){
        var palidbankdetail = $("#palidbankdetail").parent();
        showUIBlocker(palidbankdetail)
        $.post(SITE_URL+"cloud/plaid_users/pullPlaidBank", {'userid':window.encodeduserid},function (data) {
            $("#palidbankdetail").html(data);
        }).done(function() {
            $(palidbankdetail).unblock();
        });
    }
    function loanpullPlaidPaystubk(){
        var palidpaystub = $("#palidpaystub").parent();
        showUIBlocker(palidpaystub)
        $.post(SITE_URL+"cloud/plaid_users/pullPlaidPaystub", {'userid':window.encodeduserid},function (data) {
            $("#palidpaystub").html(data);
        }).done(function() {
            $(palidpaystub).unblock();
        });
    }
    //Atomic Data
    var atomicObj;

    function loadAtomicblock() {
        var employerdetail = $("#employerdetail").parent();
        showUIBlocker(employerdetail)
        $.post(SITE_URL + "cloud/atomic/atomic_incomes/getrecord", {
            userid: window.userid
        }, function(resp) {
            if (resp.status) {
                $("#employerdetail").html(resp.view);
                atomicObj = resp.atomicObj;
                loadatomicbalance();
                //loadatomicstatement();
                pullEmployer();
                pullEmployeIdentity();
            } else {
                $("#employerdetail").html(resp.message);
            }
        }, 'json').done(function() {
            $(employerdetail).unblock();
        });
    }


function pullEmployer(){
    $("#employerdetail .employer").each(function(index) {
        var ele = $(this);
        showUIBlocker(ele.parent())
        
        var recordid = ele.attr('rel-accountid');
        $.post(SITE_URL + "cloud/atomic/atomic_incomes/pullEmployer", {
            recordid:recordid
        }, function(data) {
            if(!data.status){
                alert(data.message);return;
            }
            var _return='<p>\
                <strong>Employer Name :</strong>\
                '+data.result.data[0].employment.employer.name+'</p>'+
            '<p>\
            <strong>Employee Type :</strong>\
            '+data.result.data[0].employment.employeeType+'</p>'+
            '<p>\
            <strong>Job Title :</strong>\
            '+data.result.data[0].employment.jobTitle+'</p>'+
            '<p>\
                <strong>Job Date :</strong>\
                '+data.result.data[0].employment.startDate+'</p>'+
            '';
        $("#employerdetail #employer_"+recordid).html(_return);
        }, 'json').done(function() {
            $(ele.parent()).unblock();
        });
    });
   
}

function pullEmployeIdentity(){
    $("#employerdetail .employee").each(function(index) {
        var ele = $(this);
        showUIBlocker(ele.parent())
        
        var recordid = ele.attr('rel-accountid');
        $.post(SITE_URL + "cloud/atomic/atomic_incomes/pullEmployeIdentity", {
            recordid:recordid
        }, function(data) {
            if(!data.status){
                alert(data.message);return;
            }
            var _return='<p>\
                <strong>Employee Name :</strong>\
                '+data.result.data[0].identity.firstName +' '+data.result.data[0].identity.lastName+'</p>'+
            '<p>\
            <strong>Email :</strong>\
            '+data.result.data[0].identity.email+'</p>'+
            '<p>\
            <strong>Phone :</strong>\
            '+data.result.data[0].identity.phone+'</p>'+
            '<p>\
                <strong>Address :</strong>\
                '+data.result.data[0].identity.address+' '+data.result.data[0].identity.city+' '+data.result.data[0].identity.state+' '+data.result.data[0].identity.postalCode+'</p>'+
            '';
            $("#employee_"+recordid).html(_return);
        }, 'json').done(function() {
            $(ele.parent()).unblock();
        });
    });
}

    function loadatomicbalance() {
        $("#employerdetail .atomicbalance").each(function(index) {
            var ele = $(this);
            showUIBlocker(ele.parent())
            console.log(index + ": " + ele.attr('rel-linkedAccount'));
            var linkedAccount = ele.attr('rel-linkedAccount');
            $.post(SITE_URL + "cloud/atomic/atomic_incomes/getAtomicbalance", {
                userid: window.userid,
                'linkedAccount': linkedAccount
            }, function(resp) {
                if(!resp.status){
                    alert(resp.message);return;
                }
                let incom='';
                for (let [k, value] of Object.entries(resp.result.income)) {
                    if(k=='nextExpectedPayDate'){
                        incom +='<p><strong>'+k+'</strong>: '+new Date(value).toDateString()+'</p>';
                    }else{
                        incom +='<p><strong>'+k+'</strong>: '+value+'</p>';
                    }
                }
                ele.html(incom);
            }, 'json').done(function() {
                $(ele.parent()).unblock();
            });
        });
    }

    function loadatomicstatement(linkAccount, linkid) {
        showUIBlocker($("tr#empstatement_" + linkid))
        $.post(SITE_URL + "cloud/atomic/atomic_incomes/empstatement", {
            userid: window.userid,
            'linkedAccount': linkAccount
        }, function(resp) {
            if (resp.status) {
                $("td#empstatement_" + linkid).html(resp.statement);
            } else {
                $("td#empstatement_" + linkid).html(resp.message);
            }
        }, 'json').done(function() {
            $($("td#empstatement_" + linkid)).unblock();
            initcollapse();
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