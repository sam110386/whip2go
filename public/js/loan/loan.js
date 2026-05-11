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
        $.post(SITE_URL+"admin/plaid_users/pullPlaidBank", {'userid':window.encodeduserid},function (data) {
            $("#palidbankdetail").html(data);
        }).done(function() {
            $(palidbankdetail).unblock();
        });
    }
    function loanpullPlaidPaystubk(){
        var palidpaystub = $("#palidpaystub").parent();
        showUIBlocker(palidpaystub)
        $.post(SITE_URL+"admin/plaid_users/pullPlaidPaystub", {'userid':window.encodeduserid},function (data) {
            $("#palidpaystub").html(data);
        }).done(function() {
            $(palidpaystub).unblock();
        });
    }
    
    var atomicObj;

    function loadMeasureOneblock() {
        var employerdetail = $("#employerdetail").parent();
        showUIBlocker(employerdetail)
        $.post(SITE_URL + "admin/measureone/incomes/getrecord", {
            userid: window.userid
        }, function(resp) {
            if (resp.status) {
                $("#employerdetail").html(resp.view);
                atomicObj = resp.atomicObj;
            } else {
                $("#employerdetail").html(resp.message);
            }
        }, 'json').done(function() {
            $(employerdetail).unblock();
        });
    }


    function loadmeasureonestatement(recordid) {
        showUIBlocker($("tr#empstatement_" + recordid))
        $.post(SITE_URL + "admin/measureone/incomes/pullMeasureOneIncomeDetails", {
            recordid: recordid
        }, function(resp) {
            if (resp.status) {
                $("td#empstatement_" + recordid).html(resp.view);
            } else {
                $("td#empstatement_" + recordid).html(resp.message);
            }
        }, 'json').done(function() {
            $($("td#empstatement_" + recordid)).unblock();
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