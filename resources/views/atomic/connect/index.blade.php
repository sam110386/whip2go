<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no" />
    <link rel="stylesheet" href="{{ asset('css/theme2/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/core.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/colors.css') }}">
    <link rel="stylesheet" href="{{ asset('css/driveitawaystyle.css') }}">
</head>
<body class="text-center">
    <input type="hidden" value="{{ $userid }}" id="userToken" />
    <div class="page-container login-container">
        <div class="page-content">
            <div class="content-wrapper">
                <div class="content">
                    <div class="panel panel-body login-form hidden" id="primaryblock">
                        <div class="form-group has-feedback has-feedback-left row text-center mt-20">
                            <h4 class="content-group">Connect With Employers</h4>
                        </div>
                        <div class="content-group">&nbsp;</div>
                        <div class="form-group has-feedback has-feedback-left row">
                            <button id="link-button" class="btn btn-primary bg-teal btn-block btn-lg">Add Additional Employers<i class="glyphicon glyphicon-circle-arrow-right position-right"></i></button>
                        </div>
                        <div class="form-group has-feedback has-feedback-left row">
                            <a class="btn btn-primary bg-teal btn-block btn-lg" href="{{ config('app.url') }}plaid/paystub/{{ $userid }}">I Can't Find My Employer<i class="glyphicon glyphicon-circle-arrow-right position-right"></i></a>
                        </div>
                        <div class="content-group">&nbsp;</div>
                    </div>
                    <div class="panel panel-body login-form" id="congratulation" style="display: none;">
                        <div class="content-group">&nbsp;</div>
                        <div class="text-center">
                            <div class="col-xs-11">
                                <h4 class="content-group text-info text-uppercase">Congratulations!!! You are done.</h4>
                            </div>
                        </div>
                        <div class="content-group">&nbsp;</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.3/jquery.min.js"></script>
    <script src="{{ asset('Atomic/js/atomic.js') }}"></script>
    <script type="text/javascript">
    (function($) {
        let recordid = '';
        let activate = {{ $active ? 'true' : 'false' }};
        function initilize(){
            var handler = Atomic.transact({
                config: {
                    publicToken: "{{ $token }}",
                    tasks: [{ product: Product.VERIFY }],
                    language: "en",
                    metadata: { "userid": "{{ $userid }}" }
                },
                onInteraction: interaction => {
                    if (interaction.value.state == 'completed') {
                        var userId = $("#userToken").val();
                        $.post("{{ config('app.url') }}/atomic/connect/saveUser", {
                            "_token": "{{ csrf_token() }}",
                            "isAjax": true, userId: userId,
                            companyId: interaction.value.companyId, company: interaction.value.company,
                            customerId: interaction.value.customerId, payrollId: interaction.value.payrollId
                        }, function(resp) {
                            if (!resp.status) alert(resp.msg);
                            recordid = resp.recordid;
                        }, 'json');
                    }
                },
                onFinish: data => {
                    var userId = $("#userToken").val();
                    $.post("{{ config('app.url') }}/atomic/connect/saveTask", {
                        "_token": "{{ csrf_token() }}",
                        "isAjax": true, userId: userId, taskId: data.taskId, recordid: recordid
                    }, function(resp) {
                        if (!resp.status) alert(resp.msg);
                    }, 'json');
                    $("#primaryblock").removeClass('hidden');
                },
                onClose: data => {
                    $("#primaryblock").removeClass('hidden');
                    window.ReactNativeWebView.postMessage("Button Clicked!!");
                }
            });
        }
        $('#link-button').on('click', function(e) { initilize(); });
        if(activate) { initilize(); } else { $("#primaryblock").removeClass('hidden'); }
    })(jQuery);
    </script>
</body>
</html>
