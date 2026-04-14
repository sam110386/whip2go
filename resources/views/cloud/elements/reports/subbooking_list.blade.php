<ul>
@forelse(($subLog ?? []) as $log)
    <li>{{ is_array($log) ? json_encode($log) : ($log->id ?? json_encode($log)) }}</li>
@empty
    <li>No sub-booking records.</li>
@endforelse
</ul>
