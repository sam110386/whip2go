<legend class="text-bold">New Rental :</legend>
<div class="col-lg-12">
    <table class="table table-responsive">
        <thead>
            <tr>
                <th>Initial Fee</th>
                <th>Monthly Mile</th>
                <th>Day Rent with EMF</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($result['rental_options'] as $initial => $rental)
                @php
                    $keyParts = explode('X', $initial);
                    $initialFee = $keyParts[0] ?? 0;
                    $monthlyMile = end($keyParts);
                    $radioValue = $initial . 'X' . $rental['dayEmfRent'] . 'X' . $rental['emf'];
                    $isChecked = ((int) $result['pre_miles'] === (int) $monthlyMile);
                @endphp
                <tr>
                    <td>
                        <input type="radio" name="newrental" value="{{ $radioValue }}" {{ $isChecked ? 'checked="checked"' : '' }}>
                        {{ $initialFee }}
                    </td>
                    <td>{{ $monthlyMile }}</td>
                    <td>{{ $rental['dayEmfRent'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>