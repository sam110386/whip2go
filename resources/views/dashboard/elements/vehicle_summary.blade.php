{{-- Cake `Elements/dashboard/vehicle_summary.ctp` --}}
@php
    $tv = max(1, (int)($totalVehicles ?? 0));
    $pct = static fn (int $n) => number_format(($n * 100) / $tv, 2);
@endphp
<div class="panel panel-flat">
    <div class="panel-heading text-center">
        <h6 class="panel-title">Vehicle Summary</h6>
    </div>
    <div class="table-responsive">
        <table class="table text-nowrap">
            <tbody>
                <tr>
                    <td><h6 class="text-success">Available</h6></td>
                    <td><span class="text-success-600"><i class="icon-stats-growth2 position-left"></i> {{ $pct((int)($availableVehicles ?? 0)) }}%</span></td>
                    <td><h6 class="text-semibold">{{ (int)($availableVehicles ?? 0) }}</h6></td>
                </tr>
                <tr>
                    <td><h6 class="text-primary">Active</h6></td>
                    <td><span class="text-success-600"><i class="icon-stats-growth2 position-left"></i> {{ $pct((int)($activeVehicles ?? 0)) }}%</span></td>
                    <td><h6 class="text-semibold">{{ (int)($activeVehicles ?? 0) }}</h6></td>
                </tr>
                <tr>
                    <td><h6 class="text-warning">Booked</h6></td>
                    <td><span class="text-success-600"><i class="icon-stats-growth2 position-left"></i> {{ $pct((int)($bookedVehicles ?? 0)) }}%</span></td>
                    <td><h6 class="text-semibold">{{ (int)($bookedVehicles ?? 0) }}</h6></td>
                </tr>
                <tr>
                    <td><h6 class="text-danger">Waitlist</h6></td>
                    <td><span class="text-success-600"><i class="icon-stats-growth2 position-left"></i> {{ $pct((int)($waitlistVehicles ?? 0)) }}%</span></td>
                    <td><h6 class="text-semibold">{{ (int)($waitlistVehicles ?? 0) }}</h6></td>
                </tr>
                <tr>
                    <td><h6 class="text-info">Total</h6></td>
                    <td><span class="text-success-600"><i class="icon-stats-growth2 position-left"></i> 100.00%</span></td>
                    <td><h6 class="text-semibold">{{ (int)($totalVehicles ?? 0) }}</h6></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
