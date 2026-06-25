<div class="panel">
    <div class="panel-body">
        <form action="{{ url('admin/featured_vehicles/addExistingStep2') }}" method="POST" class="form-horizontal"
            id="FeaturedVehicleAddNewVariantForm">
            @csrf

            <input type="hidden" name="Vehicle[attributes]" id="attributeConfig"
                value='{{ data_get($vehicle, 'config', '') }}'>

            @if(!empty($childs))
                <div class="row">
                    <div class="col-md-12">
                        <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Stock#</th>
                                    <th width="10%">VIN</th>
                                    <th width="10%">Dealer Selling Price:</th>
                                    <th width="10%">Listed Selling Price</th>
                                    <th width="35%">Variation Config</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($childs as $child)
                                    @php
                                        $checkboxValue = json_encode([
                                            'id' => data_get($child, 'id'),
                                            'stock_no' => data_get($child, 'stock_no'),
                                            'vin_no' => data_get($child, 'vin_no'),
                                            'msrp' => data_get($child, 'msrp'),
                                            'premium_msrp' => data_get($child, 'premium_msrp'),
                                            'config' => data_get($child, 'config'),
                                            'old' => in_array(data_get($child, 'id'), $existsVariants),
                                        ]);
                                        $configArr = json_decode(data_get($child, 'config', ''), true) ?? [];
                                    @endphp
                                    <tr>
                                        <td>
                                            <input name="Vehicle[variations][]" type="checkbox" value='{{ $checkboxValue }}' {{ in_array(data_get($child, 'id'), $existsVariants) ? 'checked' : '' }} />
                                        </td>
                                        <td>
                                            {{ data_get($child, 'stock_no')}}
                                        </td>
                                        <td>
                                            {{ data_get($child, 'vin_no') }}
                                        </td>
                                        <td>
                                            {{ data_get($child, 'msrp') }}
                                        </td>
                                        <td>
                                            {{ data_get($child, 'premium_msrp') }}
                                        </td>
                                        <td>
                                            {!! implode(', ', array_map(fn($k, $v) => "<strong>$k:</strong> $v", array_keys($configArr), $configArr)) !!}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="form-group pt-10">
                            <label class="col-lg-6 control-label">&nbsp;</label>
                            <div class="col-lg-2">
                                <button type="button" class="btn left-margin btn-primary w-100"
                                    onClick="addExistingStep2()">
                                    Next
                                </button>
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