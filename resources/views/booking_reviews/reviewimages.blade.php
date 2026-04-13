@if(!empty($result['initial']))
    <h4>Initial</h4>
    <pre>{{ json_encode($result['initial']['CsOrderReview'] ?? [], JSON_PRETTY_PRINT) }}</pre>
@endif
@if(!empty($result['final']))
    <h4>Final</h4>
    <pre>{{ json_encode($result['final']['CsOrderReview'] ?? [], JSON_PRETTY_PRINT) }}</pre>
@endif
