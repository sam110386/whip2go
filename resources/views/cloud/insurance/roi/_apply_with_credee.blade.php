@extends('layouts.main')

@section('content')
<div class="row ">
    @if(session('flash_message'))
        <div class="alert alert-success">{{ session('flash_message') }}</div>
    @endif
    @if(session('flash_error'))
        <div class="alert alert-danger">{{ session('flash_error') }}</div>
    @endif
</div>
<div class="panel">
    <div class="panel-body">
        <form class="form-horizontal" action="">
            <legend>Good news!!</legend>
            <div class="form-group">
                <div class="col-lg-12 col-sm-12">
                    <p class="">Good news! The insurance quote has been reviewed and it appears affordable. We need you to get insurance in place now.</p>
                    <p class="">If needed, personal loans are available that can be used to help you pay for the insurance. DriveItAway is partnered with Credee. If you are approved with DriveItAway, you will be approved to get this loan. The loan can be used to pay the upfront fee needed to purchase the insurance. It can also be used to pay the remaining monthly insurance premium installments if you'd prefer to pay your insurance weekly or biweekly instead of monthly.</p>
                    <p class="">The benefit to working with Credee is these loans are reportable to the credit bureaus. Using this finance option and paying the loans down should help improve your credit.</p>

                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-12 col-sm-12">
                    <div class="col-lg-6 col-sm-6 text-center form-group">
                    
                    <button class="btn btn-primary w-100" id="Apply_with_Credee" data-credee-button-code="prod_23xdnxo8kq" data-credee-auth_token="16a4b243d552993571d0395f196cb14f">
                    Apply with Credee
                    </button>
                    </div>
                    <div class="col-lg-6 col-sm-6 text-center ">
                        <a href="{{ $nothanksurl }}" class="btn btn-danger w-100">No Thanks <i class="icon-arrow-right8 position-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-12 col-sm-12 text-center">
                    <p class="text-danger"><em>Once this is done, we will issue the virtual card that you can use to purchase the policy. Please let us know if you have any questions.</em></p>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    // definition
function loadScript(scriptUrl) {
  const script = document.createElement('script');
  script.src = scriptUrl;
  document.body.appendChild(script);
  return new Promise((res, rej) => {
    script.onload = function() {
      res();
    }
    script.onerror = function () {
      rej();
    }
  });
}
// use
loadScript('https://common.credee.com/credee-lead-gen-iframe-live.js')
  .then(() => {
    console.log('Script loaded!');
    $('#Apply_with_Credee').attr("style",'width:100%');
    setTimeout(function(){
        $('#Apply_with_Credee').attr("style",'width:100%');
    }, 1000);
    setTimeout(function(){
        $('#Apply_with_Credee').attr("style",'width:100%');
    }, 3000);
  })
  .catch(() => {
    console.error('Script loading failed! Handle this error');
  });
</script>
@endsection
