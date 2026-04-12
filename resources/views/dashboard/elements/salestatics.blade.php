{{-- Cake `Elements/dashboard/salestatics.ctp` --}}
@php
    $r = $report ?? null;
    $days = $r ? (int)($r->days ?? 0) : 0;
    $billed = $r ? (float)($r->total_billed ?? 0) : 0.0;
    $gross = $r ? (float)($r->gross_revenue ?? 0) : 0.0;
@endphp
<div class="row text-center">
    <div class="col-md-4">
        <div class="content-group">
            <h5 class="text-semibold no-margin"><i class="icon-calendar5 position-left text-slate"></i> {{ $days }}</h5>
            <span class="text-muted text-size-small">Usage Days</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="content-group">
            <h5 class="text-semibold no-margin"><i class="icon-calendar52 position-left text-slate"></i> ${{ number_format($billed, 2) }}</h5>
            <span class="text-muted text-size-small">Total Usage</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="content-group">
            <h5 class="text-semibold no-margin"><i class="icon-cash3 position-left text-slate"></i> ${{ number_format($gross, 2) }}</h5>
            <span class="text-muted text-size-small">Net Usage</span>
        </div>
    </div>
</div>
