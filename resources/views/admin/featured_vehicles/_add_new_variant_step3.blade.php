<div class="row">
    <div class="col-md-12">
        <input type="hidden" name="Vehicle[attributes]" value='{{ json_encode($attributes) }}' />
        @if(!empty($variations))
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
                    @foreach($variations as $key => $variation)
                        <tr id="row{{ $key }}">
                            <td>{{ $key }}<input name="Vehicle[varitaions][{{ $key }}][id]" type="hidden" value="{{ $variation['id'] }}" /></td>
                            <td>{{ $variation['vin_no'] }}</td>
                            <td><input name="Vehicle[varitaions][{{ $key }}][dprice]" class="form-control required number" value="{{ $variation['dprice'] }}" /></td>
                            <td><input name="Vehicle[varitaions][{{ $key }}][lprice]" class="form-control required number" value="{{ $variation['lprice'] }}" /></td>
                            <td>{!! implode(', ', array_map(fn($k, $v) => "<strong>$k:</strong> $v", array_keys($variation['config']), $variation['config'])) !!}</td>
                            <td>
                                <a href="#" onclick="return removeVariationRow('{{ $key }}');"><i class="icon-trash"></i></a>
                                <input type="hidden" value='{{ json_encode([$variation['vin_no'] => $variation['config']]) }}' name="Vehicle[varitaions][{{ $key }}][config]" />
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
