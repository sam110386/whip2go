<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Initial review</title></head>
<body>
<h1>Initial review</h1>
<form method="post" action="/booking_reviews/initial/{{ base64_encode((string)$orderid) }}">
    <input type="hidden" name="CsOrderReview[id]" value="{{ $CsOrderReview['CsOrderReview']['id'] ?? '' }}">
    <p><textarea name="CsOrderReview[details]" rows="5" style="width:100%;">{{ $CsOrderReview['CsOrderReview']['details'] ?? '' }}</textarea></p>
    <p><input type="text" name="CsOrderReview[mileage]" value="{{ $CsOrderReview['CsOrderReview']['mileage'] ?? 0 }}"></p>
    <p><button type="submit">Save</button> <a href="/booking_reviews/nonreview">Back</a></p>
</form>
@if(!empty($pickup_data))
    <pre>{{ json_encode($pickup_data, JSON_PRETTY_PRINT) }}</pre>
@endif
</body>
</html>
