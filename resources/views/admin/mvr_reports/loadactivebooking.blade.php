@include('mvr_reports.partials.active_bookings_table', [
    'bookings' => $bookings,
    'reservations' => $reservations,
    'formatMvrDt' => $formatMvrDt,
])
