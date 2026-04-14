<div class="row">
    <div class="col-md-12">
        <input type="hidden" name="Vehicle[attributes]" value='{{ $attributes ?? '' }}' />
        @if(!empty($varitaions))
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
                    @foreach($varitaions as $options)
                        @php
                            $tmpstock = $options['Variant']['stock_no'];
                            $vn = $options['Variant']['vin_no'];
                            $conf = json_decode($options['Variant']['config'] ?? '', true) ?? [];
                        @endphp
                        <tr id="row{{ $tmpstock }}">
                            <td>{{ $tmpstock }}<input name="Vehicle[varitaions][{{ $tmpstock }}][id]" type="hidden" value="{{ $options['Variant']['id'] }}" /></td>
                            <td>{{ $vn }}</td>
                            <td><input name="Vehicle[varitaions][{{ $tmpstock }}][dprice]" class="form-control required number" value="{{ $options['Variant']['msrp'] }}" /></td>
                            <td><input name="Vehicle[varitaions][{{ $tmpstock }}][lprice]" class="form-control required number" value="{{ $options['Variant']['premium_msrp'] }}" /></td>
                            <td>{!! implode(', ', array_map(fn($k, $v) => "<strong>$k:</strong> $v", array_keys($conf), $conf)) !!}</td>
                            <td>
                                <a href="#" onclick="return removeVariationRow('{{ $tmpstock }}','{{ $options['Variant']['id'] }}');"><i class="icon-trash"></i></a>
                                <input type="hidden" value='{{ json_encode([$vn => $conf]) }}' name="Vehicle[varitaions][{{ $tmpstock }}][config]" />
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
