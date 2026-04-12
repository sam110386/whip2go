<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Final review</title></head>
<body>
<h1>Final review</h1>
<form method="post" action="/booking_reviews/finalreview/{{ base64_encode((string)$orderid) }}">
    <input type="hidden" name="CsOrderReview[id]" value="{{ $CsOrderReview['CsOrderReview']['id'] ?? '' }}">
    <p><textarea name="CsOrderReview[details]" rows="5" style="width:100%;">{{ $CsOrderReview['CsOrderReview']['details'] ?? '' }}</textarea></p>
    <p>Odometer <input type="text" name="CsOrderReview[mileage]" value="{{ $CsOrderReview['CsOrderReview']['mileage'] ?? 0 }}"></p>
    <p>Cleaned?
        <select name="CsOrderReview[is_cleaned]">
            <option value="1" @selected((int)($CsOrderReview['CsOrderReview']['is_cleaned'] ?? 0) === 1)>Yes</option>
            <option value="0" @selected((int)($CsOrderReview['CsOrderReview']['is_cleaned'] ?? 0) === 0)>No</option>
        </select>
    </p>
    <p>Service: <label><input type="radio" name="CsOrderReview[vehicle_service]" value="done" @checked((int)($CsOrderReview['CsOrderReview']['vehicle_service'] ?? 0) === 1)> Done</label>
        <label><input type="radio" name="CsOrderReview[vehicle_service]" value="needed" @checked((int)($CsOrderReview['CsOrderReview']['vehicle_service'] ?? 0) !== 1)> Needed</label></p>
    <p>Service date <input type="text" name="CsOrderReview[service_date]" value="{{ $CsOrderReview['CsOrderReview']['service_date'] ?? '' }}"></p>
    @foreach($extras as $key => $label)
        <p><label><input type="checkbox" name="CsOrderReview[extra][{{ $key }}]" value="1" @checked(!empty($CsOrderReview['CsOrderReview']['extra'][$key]))> {{ $label }}</label></p>
    @endforeach
    <p><button type="submit">Complete review</button> <a href="/booking_reviews/nonreview">Cancel</a></p>
</form>
</body>
</html>
