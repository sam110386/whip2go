<div class="row">
    <div class="col-md-12">
        <input type="hidden" name="Vehicle[attributes]" value='{{ json_encode($attributes) }}' />
        @if(!empty($customAttributes))
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                <thead>
                    <tr>
                        <td width="15%">Stock#</td>
                        <td width="10%">VIN</td>
                        <td width="10%">Dealer Selling Price:</td>
                        <td width="10%">Listed Selling Price</td>
                        <td width="35%">Variation Config</td>
                        <td width="10%">Action</td>
                    </tr>
                </thead>
                <tbody>
                    @php $i = 1; @endphp
                    @foreach($customAttributes as $key => $options)
                        @php
                            $tmpstock = $stock_no . '-' . $i;
                            $vn = substr($vin, 0, (16 - strlen($i))) . $i++;
                        @endphp
                        <tr id="row{{ $tmpstock }}">
                            <td>{{ $tmpstock }}<input name="Vehicle[varitaions][{{ $tmpstock }}][id]" type="hidden" value="" /></td>
                            <td>{{ $vn }}</td>
                            <td><input name="Vehicle[varitaions][{{ $tmpstock }}][dprice]" class="form-control required number" value="{{ $msrp }}" /></td>
                            <td><input name="Vehicle[varitaions][{{ $tmpstock }}][lprice]" class="form-control required number" value="{{ $premium_msrp }}" /></td>
                            <td>{!! implode(', ', array_map(fn($k, $v) => "<strong>$k:</strong> $v", array_keys($options), $options)) !!}</td>
                            <td>
                                <a href="#" onclick="return removeVariationRow('{{ $tmpstock }}');"><i class="icon-trash"></i></a>
                                <input type="hidden" value='{{ json_encode([$vn => $options]) }}' name="Vehicle[varitaions][{{ $tmpstock }}][config]" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="form-group">
                Sorry, seems you didnt choose attributes in previous step
            </div>
        @endif
    </div>
</div>
