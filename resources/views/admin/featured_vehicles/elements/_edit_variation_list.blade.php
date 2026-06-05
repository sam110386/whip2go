<div class="row">
    <div class="col-md-12">
        <input type="hidden" name="Vehicle[attributes]" value='{{ $attributes ?? '' }}' />

        @if($vehicleVariants && count($vehicleVariants) > 0)
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
                    @foreach($vehicleVariants as $options)
                        @php
                            $variant = $options->variant;
                            if ($variant) {
                                $tmpstock = $variant->stock_no;
                                $vn = $variant->vin_no;
                                $msrp = $variant->msrp;
                                $premium_msrp = $variant->premium_msrp;
                                $conf = json_decode($variant->config ?? '', true) ?? [];
                            } else {
                                continue;
                            }
                        @endphp
                        <tr id="row{{ $tmpstock }}">
                            <td>
                                {{ $tmpstock }}
                                <input name="Vehicle[varitaions][{{ $tmpstock }}][id]" type="hidden"
                                    value="{{ $variant->id }}" />
                            </td>
                            <td>
                                {{ $vn }}
                            </td>
                            <td>
                                <input name="Vehicle[varitaions][{{ $tmpstock }}][dprice]" class="form-control required number"
                                    value="{{ $msrp }}" />
                            </td>
                            <td>
                                <input name="Vehicle[varitaions][{{ $tmpstock }}][lprice]" class="form-control required number"
                                    value="{{ $premium_msrp }}" />
                            </td>
                            <td>
                                {!! implode(', ', array_map(fn($k, $v) => "<strong>$k:</strong> $v", array_keys($conf), $conf)) !!}
                            </td>
                            <td>
                                <a href="#" onclick="return removeVariationRow('{{ $tmpstock }}','{{ $variant->id }}');">
                                    <i class="icon-trash"></i>
                                </a>
                                <input type="hidden" value='{{ json_encode([$vn => $conf]) }}'
                                    name="Vehicle[varitaions][{{ $tmpstock }}][config]" />
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