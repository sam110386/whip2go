<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no" />
    <link rel="stylesheet" href="{{ asset('css/theme2/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/core.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/icons/icomoon/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/colors.css') }}">
    <link rel="stylesheet" href="{{ asset('css/driveitawaystyle.css') }}">
    <style type="text/css">
        .icons-list a[data-action]:after {
            font-size: 30px;
            min-width: 30px;
        }
        m1-link .m1-component{width: 100% !important;}
    </style>
</head>
<body class="text-center">
    <div class="page-container login-container">
        <div class="page-content">
            <div class="content-wrapper">
                <div id="mainblock">
                    <m1-link></m1-link>
                </div>
                <div class="content">
                    <div class="panel panel-body login-form" id="congratulation" style="display: none;">
                        <div class="content-group">&nbsp;</div>
                        <div class="text-center">
                            <div class="col-xs-11">
                                <h4 class="content-group text-info text-uppercase">Congratulations!!! You are done.</h4>
                            </div>
                        </div>
                        <div class="content-group">&nbsp;</div>
                        <div class="content-group">&nbsp;</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.3/jquery.min.js"></script>
    <script src="{{ config('legacy.MeasureOne.script') }}"></script>
    <script type="text/javascript">
        (function($) {
            var userId = "{{ $userid }}";
            var orderruleid = "{{ $orderruleid }}";
            var config = {
                access_key: "{{ $token['access_token'] }}",
                host_name: "{{ config('legacy.MeasureOne.host_name') }}",
                datarequest_id: "{{ $token['id'] }}",
                branding: {
                    styles: {
                        primary_dark: "#2ea3ff",
                        primary_light: "#2ea3ff",
                        secondary_color: "#ffffff",
                        max_width:"100%",
                        width: "unset"
                    }
                },
                options: {
                    "display_profile": false
                }
            };
            function initilize() {
                var m1_widget = document.querySelector("m1-link");
                m1_widget.setAttribute("config", JSON.stringify(config));
                m1_widget.addEventListener('datasourceConnected', (event) => {
                    console.log(event);
                    $.post("{{ config('app.url') }}/measureone/connect/saveinsurance", {
                        userId: userId,
                        detail: event.detail.data,
                        orderruleid: orderruleid,
                        _token: "{{ csrf_token() }}"
                    }, function(resp) {
                        $("#mainblock").addClass('hidden');
                        if (!resp.status) {
                            alert(resp.msg);
                        } else {
                            var win = window.open("about:blank", "_self");
                            win.close();
                        }
                    }, 'json');
                });
                m1_widget.addEventListener('itemsCreated', (event) => {
                    console.log(event);
                    $.post("{{ config('app.url') }}/measureone/connect/saveinsurance", {
                        userId: userId,
                        detail: event.detail.data,
                        orderruleid: orderruleid,
                        _token: "{{ csrf_token() }}"
                    }, function(resp) {
                        $("#mainblock").addClass('hidden');
                        if (!resp.status) {
                            alert(resp.msg);
                        } else {
                            var win = window.open("about:blank", "_self");
                            win.close();
                        }
                    }, 'json');
                });
                m1_widget.addEventListener('exitRequested', (event) => {
                    console.log(event);
                    $("#mainblock").addClass('hidden');
                });
                m1_widget.addEventListener('credentialsNotObtained', (event) => {
                    window.location.href = window.location.href;
                    $("#mainblock").addClass('hidden');
                });
                m1_widget.addEventListener('tokenExpired', (event) => {
                    window.location.href = window.location.href;
                    $("#mainblock").addClass('hidden');
                });
            }
            initilize();
            $("#mainblock").removeClass('hidden');
        })(jQuery);
    </script>
</body>
</html>
