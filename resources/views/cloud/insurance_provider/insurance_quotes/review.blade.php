@extends('layouts.main')
@section('content')
<script type="text/javascript">
   $(document).ready(function(){
    $(".reviewwrap").mouseenter(function(){
        $(this).find('.panel.panel-flat').addClass('animated pulse');
    }).mouseleave(function(){
        $(this).find('.panel.panel-flat').removeClass('animated pulse');
    });
   });
</script>
<div class="row ">
    @if(session('flash_message'))
        <div class="alert alert-success">{{ session('flash_message') }}</div>
    @endif
    @if(session('flash_error'))
        <div class="alert alert-danger">{{ session('flash_error') }}</div>
    @endif
</div>

<div class="">
    <div class="">
        <form action="{{ url('/insuprovider/quotes/review') }}" method="POST" class="form-horizontal">
            @csrf
            <div class="row">
                
                @if(!empty($providers))
                    <div class="col-lg-12">
                        <img src="{{ asset('img/insurance_providers/lincoln-insurance-logo-blue.webp') }}" class="img-responsive mb-3">
                    </div>
                    @if(in_array('25/50/25', $providers))
                    <!-- Basic start here -->
                    <div class="col-lg-4 reviewwrap">
                        <div class="panel panel-flat">
                            <div class="panel-heading">
                                <h6 class="panel-title">Basic</h6>
                            </div>
                            <div class="panel-body">
                                <span class="w-100 mb-5 text-semibold"><i class="icon-file-presentation font-12"></i> Policy Coverage</span>
                                <p class="mb-5 reviewbox"><small class="display-block pull-left text-muted">Bodily injury (BI)</small><span class="text-semibold pull-right price">$25K/$50K</span></p>
                                <p class="mb-5 reviewbox"><small class="display-block pull-left text-muted">Property damage (PD)</small><span class="text-semibold pull-right price">$25K</span></p>
                                <p class="mb-5 reviewbox"><small class="display-block pull-left text-muted">Persoanl injury protection</small><span class="text-semibold pull-right price">none</span></p>
                                <span class="w-100 mb-5 text-semibold"><i class="icon-car font-12"></i> Vehicle Coverage</span>
                                <p class="mb-5 reviewbox"><small class="display-block pull-left text-muted">Collision (deductible)</small><span class="text-semibold pull-right price">$1K</span></p>
                                <p class="mb-5 reviewbox"><small class="display-block pull-left text-muted">Comprehensive (deductible)</small><span class="text-semibold pull-right price">$1K</span></p>
                                <div class="w-100 text-center">
                                    <a href="{{ config('app.url') }}/insuprovider/quotes/providers/1/{{ $orderandusers }}" class="btn btn-blank btn-rounded">Show quotes on this coverage</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Basic end here -->
                    @endif
                    @if(in_array('50/100/50', $providers))
                    <!-- Better start here -->
                    <div class="col-lg-4 reviewwrap ">
                        <div class="panel panel-flat bettershadow">
                            <div class="panel-heading">
                                <h6 class="panel-title">Better</h6>
                                <p class="heading-elements">
                                    <span class="label bg-success label-rounded">Our pick for you</span>
                                </p>
                            </div>

                            <div class="panel-body">
                                <span class="w-100 mb-5 text-semibold"><i class="icon-file-presentation font-12"></i> Policy Coverage</span>
                                <p class="mb-5 reviewbox"><small class="display-block pull-left text-muted">Bodily injury (BI)</small><span class="text-semibold pull-right price">$50K/$100K</span></p>
                                <p class="mb-5 reviewbox"><small class="display-block pull-left text-muted">Property damage (PD)</small><span class="text-semibold pull-right price">$50K</span></p>
                                <p class="mb-5 reviewbox"><small class="display-block pull-left text-muted">Persoanl injury protection</small><span class="text-semibold pull-right price">none</span></p>
                                <span class="w-100 mb-5 text-semibold"><i class="icon-car font-12"></i> Vehicle Coverage</span>
                                <p class="mb-5 reviewbox"><small class="display-block pull-left text-muted">Collision (deductible)</small><span class="text-semibold pull-right price">$1K</span></p>
                                <p class="mb-5 reviewbox"><small class="display-block pull-left text-muted">Comprehensive (deductible)</small><span class="text-semibold pull-right price">$1K</span></p>
                                <div class="w-100 text-center">
                                    <a href="{{ config('app.url') }}/insuprovider/quotes/providers/2/{{ $orderandusers }}"  class="btn btn-danger btn-rounded">Show quotes on this coverage</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Better end here -->
                    @endif
                    @if(in_array('100/300/100', $providers))
                    <!-- Best start here -->
                    <div class="col-lg-4 reviewwrap">
                        <div class="panel panel-flat">
                            <div class="panel-heading">
                                <h6 class="panel-title">Best</h6>
                            </div>

                            <div class="panel-body">
                                <span class="w-100 mb-5 text-semibold"><i class="icon-file-presentation font-12"></i> Policy Coverage</span>
                                <p class="mb-5 reviewbox"><small class="display-block pull-left text-muted">Bodily injury (BI)</small><span class="text-semibold pull-right price">$100K/$300K</span></p>
                                <p class="mb-5 reviewbox"><small class="display-block pull-left text-muted">Property damage (PD)</small><span class="text-semibold pull-right price">$50K</span></p>
                                <p class="mb-5 reviewbox"><small class="display-block pull-left text-muted">Persoanl injury protection</small><span class="text-semibold pull-right price">none</span></p>
                                <span class="w-100 mb-5 text-semibold"><i class="icon-car font-12"></i> Vehicle Coverage</span>
                                <p class="mb-5 reviewbox"><small class="display-block pull-left text-muted">Collision (deductible)</small><span class="text-semibold pull-right price">$1K</span></p>
                                <p class="mb-5 reviewbox"><small class="display-block pull-left text-muted">Comprehensive (deductible)</small><span class="text-semibold pull-right price">$1K</span></p>
                                <div class="w-100 text-center">
                                <a href="{{ config('app.url') }}/insuprovider/quotes/providers/3/{{ $orderandusers }}" class="btn btn-blank btn-rounded">Show quotes on this coverage</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Best end here -->
                    @endif
                @else
                    <div class="col-lg-12">
                        <img src="{{ asset('img/DriveitawayBluelogo.png') }}" class="img-responsive">
                    </div>
                    
                    <h4 class="text-center">Our team will be in touch shortly with insurance options. Stay tuned!</h4>   
                @endif    
            </div>
       
        </form>
    </div>
</div>
@endsection
