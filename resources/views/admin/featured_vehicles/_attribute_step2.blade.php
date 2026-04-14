<div class="panel">
    <form action="{{ config('app.url') }}admin/featured_vehicles/loadAttributeStep2Popup" method="POST" class="form-horizontal" id="FeaturedVehicleAttributeStep2Form">
        @csrf
        <div class="panel-body">
            @if(!empty($attributes))
                @foreach($attributes as $attribute)
                    <div class="form-group">
                        <label class="col-lg-2 control-label text-bold">Option for {{ $attribute }}:<font class="requiredField">*</font></label>
                        @if(strpos(strtolower($attribute), 'color') !== false)
                            <div class="col-lg-8">
                                <select name="FeaturedVehicle[attributes][{{ $attribute }}][]" class="bootstrap-select required" data-width="100%" multiple="true">
                                    @foreach($colors as $color)
                                        <option value="{{ $color['name'] }}" data-content="<span style='display:flex;'><span style='width:20px;height:20px;background:{{ $color['hex'] }};margin-right:10px;'></span> {{ $color['name'] }}</span>"></option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="col-lg-8">
                                <textarea name="FeaturedVehicle[attributes][{{ $attribute }}]" class="required form-control alphanumericwithspace" placeholder="Like White, Black..."></textarea>
                                <em>Please enter every value in new line, without comma (,)</em>
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="form-group">
                    Sorry, seems you didnt choose attributes in previous step
                </div>
            @endif
            <div class="form-group mt-10">
                <label class="col-lg-2 control-label">&nbsp;</label>
                <div class="col-lg-2">
                    <button type="button" class="btn left-margin btn-primary w-100" onClick="featuredVehicleAttributeStep2()">Next</button>
                </div>
            </div>
        </div>
    </form>
</div>
