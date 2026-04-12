@extends('admin.layouts.app')

@section('title', 'Final booking review')

@section('content')
    <h1>Final booking review</h1>
    @if(session('success'))<p style="color:green;">{{ session('success') }}</p>@endif

    @php $co = $CsOrder['CsOrder']; $cr = $CsOrderReview['CsOrderReview']; @endphp

    <form method="post" action="{{ $basePath }}/admin_finalreview/{{ base64_encode((string)$orderid) }}" id="frmadmin">
        <input type="hidden" name="CsOrderReview[id]" value="{{ $cr['id'] ?? '' }}">
        <input type="hidden" name="CsOrderReview[cs_order_id]" value="{{ $orderid }}">

        @if(($co['deposit_type'] ?? '') === 'C')
            <p><strong>Deposit:</strong> {{ $co['deposit'] ?? '' }} {{ $co['currency'] ?? '' }}</p>
            <p><label>Refund amount<br>
                <input type="text" name="CsOrderReview[refund]" value="{{ $co['deposit'] ?? 0 }}">
            </label></p>
            <p><button type="button" class="btn btn-danger" onclick="alert('Deposit settlement is not wired in Laravel yet; use legacy or extend PaymentProcessor.');">Process (legacy)</button></p>
        @endif

        <p><label>Vehicle condition report<br>
            <textarea name="CsOrderReview[details]" rows="5" style="width:100%;">{{ $cr['details'] ?? '' }}</textarea>
        </label></p>
        <p><label>Ending odometer<br>
            <input type="text" name="CsOrderReview[mileage]" id="CsOrderReviewMileage" value="{{ $cr['mileage'] ?? 0 }}">
            <button type="button" id="btnOdo">Pull odometer (DB)</button>
        </label></p>
        <script>
            document.getElementById('btnOdo')?.addEventListener('click', function () {
                var fd = new FormData();
                fd.append('vehicle', '{{ base64_encode((string)($co['vehicle_id'] ?? 0)) }}');
                fetch('{{ $basePath }}/pullVehicleOdometer', {method: 'POST', body: fd})
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (d.status) document.getElementById('CsOrderReviewMileage').value = d.miles;
                        else alert(d.message || 'Could not read odometer');
                    });
            });
        </script>

        <p><label>Vehicle cleaned<br>
            <select name="CsOrderReview[is_cleaned]">
                <option value="0" @selected((int)($cr['is_cleaned'] ?? 0) === 0)>No</option>
                <option value="1" @selected((int)($cr['is_cleaned'] ?? 0) === 1)>Yes</option>
            </select>
        </label></p>
        <p>Vehicle service:
            <label><input type="radio" name="CsOrderReview[vehicle_service]" value="done" @checked((int)($cr['vehicle_service'] ?? 0) === 1)> Done</label>
            <label><input type="radio" name="CsOrderReview[vehicle_service]" value="needed" @checked((int)($cr['vehicle_service'] ?? 0) !== 1)> Needed</label>
        </p>
        <p><label>Service date<br>
            <input type="text" name="CsOrderReview[service_date]" value="{{ $cr['service_date'] ?? '' }}">
        </label></p>

        @foreach($extras as $key => $label)
            <p><label><input type="checkbox" name="CsOrderReview[extra][{{ $key }}]" value="1" @checked(!empty($cr['extra'][$key]))> {{ $label }}</label></p>
        @endforeach
        <p><label><input type="checkbox" name="CsOrderReview[extra][new_vehicle_body_damage]" value="1" @checked(!empty($cr['extra']['new_vehicle_body_damage']))> New vehicle body damage</label></p>
        <p><textarea name="CsOrderReview[extra][new_vehicle_body_damage_text]" rows="2" style="width:100%;" placeholder="Damage details">{{ $cr['extra']['new_vehicle_body_damage_text'] ?? '' }}</textarea></p>

        <p>
            <button type="submit" name="submit" value="save">Save only</button>
            <button type="submit" name="submit" value="update">Complete review</button>
            <a href="{{ $basePath }}/nonreview">Cancel</a>
        </p>
    </form>

    <h3>Review images</h3>
    <p>POST <code>{{ $basePath }}/saveImage</code> with multipart <code>reviewimage</code> and field <code>id={{ $cr['id'] ?? '' }}</code> (same pattern as legacy fileinput).</p>
    <ul>
        @foreach($CsOrderReviewImages ?? [] as $img)
            <li><a href="/files/reviewimages/{{ $img->image }}" target="_blank">{{ $img->image }}</a> (id {{ $img->id }})</li>
        @endforeach
    </ul>
@endsection
