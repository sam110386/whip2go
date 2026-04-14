<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
    <meta name="format-detection" content="telephone=no, email=no">
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="stylesheet" href="{{ asset('css/theme2/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/core.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/colors.css') }}">
    <link rel="stylesheet" href="{{ asset('css/driveitawaystyle.css') }}">
    <script src="{{ asset('js/assets/js/core/libraries/jquery.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/plugins/loaders/blockui.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/plugins/notifications/sweet_alert.min.js') }}"></script>
    <script src="{{ asset('js/jquery.validate.js') }}"></script>
    <style>
        html, body { margin: 0; padding: 0; width: 100%; height: 100%; overscroll-behavior: none; }
        * { box-sizing: border-box; }
        .partnerwraper { max-height: 200px; min-height: 200px; height: 100%; overflow-y: scroll; }
        .btn-primary { background-color: #2ea3ff; border: #2ea3ff; }
        ul.partnerlist li.mb-10 { cursor: pointer; }
        ul.partnerlist li.mb-10.active { background-color: #ccc; }
        ul.partnerlist li.mb-10:hover, ul.partnerlist li.mb-10:active, ul.partnerlist li.mb-10:focus { background-color: #ccc; }
    </style>
    <script type="text/javascript">
        var SITE_URL = '{{ config("app.url") }}';
        $(function() {
            $("#PromotionRuleCodeForm").validate();
            $("#PromotionRuleCodeSearch").keyup(function(e) {
                var searchString = e.target.value;
                $("ul.partnerlist li").each(function(index, value) {
                    currentName = $(value).text();
                    if (currentName.toUpperCase().indexOf(searchString.toUpperCase()) > -1) {
                        $(value).show();
                    } else {
                        $(value).hide();
                    }
                });
            });
        });

        function promoApply(promouser) {
            var ajaxUrl = SITE_URL + 'promo/promoconnect/apply';
            swal({
                title: "",
                text: "We can apply the discount associated with this employer. You will be required to connect and verify your employment with this employer after reserving the vehicle. Is that OK?",
                showCancelButton: true, confirmButtonColor: "#66BB6A", confirmButtonText: "Okay", cancelButtonText: "No",
                closeOnConfirm: true, closeOnCancel: true
            }, function(isConfirm) {
                if (isConfirm) {
                    jQuery.blockUI({ message: '<span>Checking...</span>', css: { 'z-index': '9999' } });
                    $.post(ajaxUrl, { promouser: promouser, _token: '{{ csrf_token() }}' }, function(resp) {
                        jQuery.unblockUI();
                        if (resp.status) {
                            swal({ title: "Success!", text: resp.message, confirmButtonColor: "#2196F3" }, function(){ location.href=''; });
                        } else {
                            swal({ title: "Error!", text: resp.message, confirmButtonColor: "Red" });
                        }
                    }, 'json').done(function() { jQuery.unblockUI(); });
                }
            });
        }

        function formPromoApply() {
            var ajaxUrl = SITE_URL + 'promo/promoconnect/applypromo/{{ $userid }}';
            if (!$("#PromotionRuleCodeForm").valid()) { return false; }
            jQuery.blockUI({ message: '<span>Checking...</span>', css: { 'z-index': '9999' } });
            var promo = $("#PromotionRuleCodeForm").serialize();
            $.post(ajaxUrl, promo + '&_token={{ csrf_token() }}', function(resp) {
                jQuery.unblockUI();
                if (resp.status) {
                    swal({ title: "Success!", text: resp.message, confirmButtonColor: "#2196F3" }, function(){ location.href=''; });
                } else {
                    swal({ title: "Error!", text: resp.message, confirmButtonColor: "Red" });
                }
            }, 'json').done(function() { jQuery.unblockUI(); });
        }

        function formPromoRemove() {
            var ajaxUrl = SITE_URL + 'promo/promoconnect/removepromo/{{ $userid }}';
            swal({
                title: "", text: "Are you sure you want to clear saved promotions?",
                showCancelButton: true, confirmButtonColor: "#66BB6A", confirmButtonText: "Okay", cancelButtonText: "No",
                closeOnConfirm: true, closeOnCancel: true
            }, function(isConfirm) {
                if (isConfirm) {
                    jQuery.blockUI({ message: '<span>Checking...</span>', css: { 'z-index': '9999' } });
                    $.post(ajaxUrl, { _token: '{{ csrf_token() }}' }, function(resp) {
                        jQuery.unblockUI();
                        if (resp.status) {
                            swal({ title: "Success!", text: resp.message, confirmButtonColor: "#2196F3" }, function(){ location.href=''; });
                        } else {
                            swal({ title: "Error!", text: resp.message, confirmButtonColor: "Red" });
                        }
                    }, 'json').done(function() { jQuery.unblockUI(); });
                }
            });
        }
    </script>
</head>
<body class="text-center">
    <div class="page-container login-container">
        <div class="page-content">
            <div class="contentwrapper">
                <div class="content">
                    <div class="panel panel-body login-form">
                        <div class="form-group has-feedback has-feedback-left row text-center mt-20">
                            <p class="content-group text-size-large"><i class="glyphicon glyphicon-arrow-left"></i> Search for your employer or enter your promo code now to unlock discount!</p>
                        </div>
                        <div class="row1">
                            <form class="form-horizontal" action="" method="post" id="PromotionRuleCodeForm">
                                @csrf
                                <div class="form-group">
                                    <label class="col-lg-12 control-label text-center text-size-large text-uppercase">Enter Coupon Code</label>
                                    <div class="col-xs-10">
                                        <input type="text" name="PromotionRule[code]" class="form-control required w-100" placeholder="Coupon" value="{{ $code }}" />
                                    </div>
                                    <div class="col-xs-2 text-center nopadding">
                                        @if(!empty($code))
                                            <button type="button" alt="Clear All" title="Clear All" onclick="formPromoRemove()" class="btn btn-danger btn-round"><i class="glyphicon glyphicon-remove-circle"></i></button>
                                        @else
                                            <button type="button" alt="Apply" title="Apply" onclick="formPromoApply()" class="btn btn-primary btn-round"><i class="glyphicon glyphicon-ok"></i></button>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="content-group"><i class="glyphicon glyphicon-sort"></i></div>
                        <div class="content-group row">
                            <div class="col-xs-10">
                                <input class="form-control" placeholder="Search" type="text" id="PromotionRuleCodeSearch" />
                            </div>
                            <div class="col-xs-2 text-center nopadding">
                                @if($provider)
                                    <button type="button" alt="Clear All" title="Clear All" onclick="formPromoRemove()" class="btn btn-danger btn-round"><i class="glyphicon glyphicon-remove-circle"></i></button>
                                @endif
                            </div>
                        </div>
                        <div class="form-group has-feedback has-feedback-left partnerwraper">
                            <ul class="text-left nopadding partnerlist">
                                @foreach($promos as $promo)
                                    @if(!$promo->list) @continue @endif
                                    <li alt="{{ $promo->title }}" class="w-100 mb-10 {{ $promo->id == $provider ? 'active' : '' }}" onclick="return promoApply('{{ base64_encode($promo->id . '-' . $userid) }}');">
                                        <span class="text-thin text-default">
                                            <img src="{{ config('app.url') }}img/promo/{{ $promo->logo }}" class="img-xs" />
                                            <small class="text-size-large text-highlight">{{ $promo->title }}</small>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="content-group">&nbsp;</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
