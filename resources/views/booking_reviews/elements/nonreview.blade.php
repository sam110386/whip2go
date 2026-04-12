<table border="1" cellpadding="4" width="100%">
    @foreach($nonreviews as $o)
        <tr>
            <td>{{ $o->increment_id }}</td>
            <td>{{ $o->vehicle_unique_id }}</td>
            <td><a href="/booking_reviews/initial/{{ base64_encode((string)$o->id) }}">Review</a></td>
        </tr>
    @endforeach
</table>
