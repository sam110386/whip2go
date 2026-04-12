@php
    $rollups = $rollups ?? [];
@endphp
<table style="width:100%; border-collapse:collapse; font-size:13px;" class="table table-responsive">
    <thead>
        <tr style="border-bottom:2px solid #ccc; text-align:left;">
            <th style="padding:6px;width:36px;"></th>
            <th style="padding:6px;">Booking#</th>
            <th style="padding:6px;">Status</th>
            <th style="padding:6px;">Vehicle</th>
            <th style="padding:6px;">Start</th>
            <th style="padding:6px;">End</th>
            <th style="padding:6px;">Duration</th>
            <th style="padding:6px;">Customer</th>
            <th style="padding:6px;">Mileage</th>
            <th style="padding:6px;">Rent</th>
            <th style="padding:6px;">Extra</th>
            <th style="padding:6px;">Insurance</th>
            <th style="padding:6px;">Toll</th>
            <th style="padding:6px;">DIA Fee</th>
            <th style="padding:6px;width:80px;">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($reportlists as $r)
            @php
                $isChain = ((int)($r->auto_renew ?? 0)) || ((int)($r->pto ?? 0));
                $ru = $isChain ? ($rollups[(int)($r->id ?? 0)] ?? null) : null;
                $tid = base64_encode((string) ($r->id ?? ''));
                $openTrip = $isChain ? "openCombinedBookingDetails('{$tid}')" : "openTripDetails('{$tid}', this)";
                $loadsub = "loadsubbooking('" . (int)($r->id ?? 0) . "')";
                $tz = $r->timezone ?? null;
                $fmt = function ($dt) use ($tz) {
                    if (empty($dt)) return '';
                    try {
                        return \Carbon\Carbon::parse($dt)->timezone($tz ?: config('app.timezone'))->format('m/d/Y');
                    } catch (\Throwable $e) {
                        return (string) $dt;
                    }
                };
                $daysBetween = function ($a, $b) {
                    if (empty($a) || empty($b)) return 0;
                    try {
                        return (int) abs(\Carbon\Carbon::parse($a)->startOfDay()->diffInDays(\Carbon\Carbon::parse($b)->startOfDay()));
                    } catch (\Throwable $e) {
                        return 0;
                    }
                };
                if ($isChain && $ru) {
                    $endDisp = $fmt($ru->end_datetime ?? null);
                    $dur = $daysBetween($r->start_datetime ?? null, $ru->end_datetime ?? null);
                    $mile = (float) ($ru->mileage ?? 0);
                    $rentTotal = (float) ($ru->paid_amount ?? 0);
                    $extraHtml = '<b>Extra Mile:</b>' . ($ru->extra_mileage_fee ?? 0) . ' <b>Damage:</b>' . ($ru->damage_fee ?? 0) . ' <b>Lateness:</b>' . ($ru->lateness_fee ?? 0) . ' <b>Un-Cleanness:</b>' . ($ru->uncleanness_fee ?? 0);
                    $ins = (float) ($ru->insurance ?? 0);
                    $toll = (float) ($ru->toll ?? 0);
                    $dia = (float) ($ru->dia_fee ?? 0);
                } else {
                    $endDisp = $fmt($r->end_datetime ?? null);
                    $dur = $daysBetween($r->start_datetime ?? null, $r->end_datetime ?? null);
                    $mile = ((int)($r->status ?? 0) === 3 && $r->end_odometer !== null && $r->start_odometer !== null)
                        ? ((float) $r->end_odometer - (float) $r->start_odometer)
                        : 0;
                    $rentTotal = (float)($r->rent ?? 0) + (float)($r->tax ?? 0) + (float)($r->initial_fee ?? 0)
                        + (float)($r->extra_mileage_fee ?? 0) + (float)($r->damage_fee ?? 0)
                        + (float)($r->lateness_fee ?? 0) + (float)($r->uncleanness_fee ?? 0);
                    $extraHtml = '<b>Extra Mile:</b>' . ($r->extra_mileage_fee ?? 0) . ' <b>Damage:</b>' . ($r->damage_fee ?? 0) . ' <b>Lateness:</b>' . ($r->lateness_fee ?? 0) . ' <b>Un-Cleanness:</b>' . ($r->uncleanness_fee ?? 0);
                    $ins = (float)($r->insurance_amt ?? 0) + (float)($r->dia_insu ?? 0);
                    $toll = (float)($r->toll ?? 0) + (float)($r->pending_toll ?? 0);
                    $dia = (float)($r->dia_fee ?? 0);
                }
                $statusLabel = '';
                if (!$isChain) {
                    $statusLabel = ((int)($r->status ?? 0)) === 3 ? 'Completed' : (((int)($r->status ?? 0)) === 2 ? 'Canceled' : 'Incomplete');
                }
            @endphp
            <tr id="tr_{{ $r->id }}" rel-parent="no">
                <td style="padding:6px;"></td>
                @if($isChain)
                    <td style="padding:6px;" onclick="{{ $loadsub }}">
                        <i class="icon-forward position-left text-warning-400"></i>{{ $r->increment_id ?? $r->id }}
                    </td>
                @else
                    <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $r->increment_id ?? $r->id }}</td>
                @endif
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $statusLabel }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $r->vehicle_name ?? '' }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $fmt($r->start_datetime ?? null) }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $endDisp }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $dur }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ trim(($r->renter_first_name ?? '') . ' ' . ($r->renter_last_name ?? '')) }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $mile }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $rentTotal }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{!! $extraHtml !!}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $ins }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $toll }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $dia }}</td>
                <td style="padding:6px;">
                    @if((int)($r->status ?? 0) === 3)
                        <a href="javascript:void(0)" title="Review Images" onclick="reviewimages('{{ base64_encode((string)($r->id ?? '')) }}')"><i class="icon-clipboard3"></i></a>
                    @endif
                    <a href="javascript:void(0)" title="Agreement Doc" onclick="return getagreement('{{ base64_encode((string)($r->id ?? '')) }}');"><i class="icon-file-pdf"></i></a>
                </td>
            </tr>
        @empty
            <tr><td colspan="15" style="padding:10px;">No rows found.</td></tr>
        @endforelse
    </tbody>
</table>

@if(isset($reportlists) && method_exists($reportlists, 'links'))
    {{ $reportlists->appends(request()->except('page'))->links() }}
@endif
