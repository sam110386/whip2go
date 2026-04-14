<div class="panel">
    <div class="panel-body">
        <form action="{{ config('app.url') }}admin/featured_vehicles/addExistingStep3" method="POST" class="form-horizontal" id="FeaturedVehicleAddNewVariantStep2Form">
            @csrf
            <input type="hidden" name="attributes" value='{{ json_encode($attributes) }}' />
            @if(!empty($variations))
                <div class="row">
                    <div class="col-md-12">
                        <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                            <thead>
                                <tr>
                                    <th width="15%">Stock#</th>
                                    <th width="10%">VIN</th>
                                    <th width="10%">Dealer Selling Price:</th>
                                    <th width="10%">Listed Selling Price</th>
                                    @foreach($attributes as $key => $attribute)
                                        <th width="5%">{{ $key }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($variations as $variation)
                                    @php
                                        $variation = json_decode($variation, true);
                                        $config = json_decode($variation['config'] ?? '', true);
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $variation['stock_no'] }}
                                            <input name="variations[{{ $variation['stock_no'] }}][id]" type="hidden" value="{{ $variation['id'] }}" />
                                        </td>
                                        <td>
                                            {{ $variation['vin_no'] }}
                                            <input name="variations[{{ $variation['stock_no'] }}][vin_no]" type="hidden" value="{{ $variation['vin_no'] }}" />
                                        </td>
                                        <td><input name="variations[{{ $variation['stock_no'] }}][dprice]" class="form-control required number" value="{{ $variation['msrp'] }}" /></td>
                                        <td><input name="variations[{{ $variation['stock_no'] }}][lprice]" class="form-control required number" value="{{ $variation['premium_msrp'] }}" /></td>
                                        @foreach($attributes as $key => $attribute)
                                            <td width="5%">
                                                <input type="text" value='{{ $config[$key] ?? '' }}' name="variations[{{ $variation['stock_no'] }}][config][{{ $key }}]" class="form-control required" />
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="form-group pt-10">
                            <label class="col-lg-6 control-label">&nbsp;</label>
                            <div class="col-lg-2">
                                <button type="button" class="btn left-margin btn-primary w-100" onClick="addExistingStep3()">Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="form-group">
                    Sorry, seems you didnt choose attributes in previous step
                </div>
            @endif
        </form>
    </div>
</div>
