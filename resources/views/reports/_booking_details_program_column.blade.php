@if(!empty($OrderDepositRule['OrderDepositRule']))
    @php $r = $OrderDepositRule['OrderDepositRule']; @endphp
    <div class="col-lg-6">
        <legend>Program & Fee Allocations</legend>
        <div class="form-group">
            <label class="col-lg-6">Vehicle Cost</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', (float)($r['msrp'] ?? 0) - (float)($r['initial_fee'] ?? 0)) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Vehicle Selling Price</label>
            <div class="col-lg-6">{{ $r['msrp'] ?? '' }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Vehicle Listing Price</label>
            <div class="col-lg-6">{{ $r['premium_msrp'] ?? '' }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Write Down Allocation %</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', (float)($calculation['write_down_allocation'] ?? 0)) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Finance Cost %</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', (float)($calculation['finance_allocation'] ?? 0)) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Maintenance Allocation %</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', (float)($calculation['maintenance_allocation'] ?? 0)) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Disposition Fee</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', (float)($calculation['disposition_fee'] ?? 0)) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Program Length</label>
            <div class="col-lg-6">{{ $r['num_of_days'] ?? '' }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Total Program Cost</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', (float)($calculation['total_program_cost'] ?? 0)) }}</div>
        </div>
        <legend>Program Breakdown</legend>
        <div class="form-group">
            <label class="col-lg-6">Total Write Down Allocation In Program</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', ((float)($calculation['write_down_allocation'] ?? 0)) * ((float)($calculation['total_program_cost'] ?? 0)) / 100) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Total Finance Cost In Program</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', ((float)($calculation['finance_allocation'] ?? 0)) * ((float)($calculation['total_program_cost'] ?? 0)) / 100) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Total Maintnenance Cost In Program</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', ((float)($calculation['maintenance_allocation'] ?? 0)) * ((float)($calculation['total_program_cost'] ?? 0)) / 100) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Total DIA Fee In Program</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', (float)($calculation['total_program_fee_with_dia'] ?? 0) - (float)($calculation['total_program_fee_without_dia'] ?? 0)) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Total Disposition Fee In Program</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', (float)($calculation['disposition_fee'] ?? 0)) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Total Program Cost</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', (float)($calculation['total_program_cost'] ?? 0)) }}</div>
        </div>
        <legend>Rates</legend>
        <div class="form-group">
            <label class="col-lg-6">Initial Fee</label>
            <div class="col-lg-6">{{ $r['initial_fee'] ?? '' }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Base Usage Rate</label>
            <div class="col-lg-6">{{ $r['base_rent'] ?? '' }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Included Distance</label>
            <div class="col-lg-6">{{ $r['miles'] ?? '' }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Extra Usage Rate</label>
            <div class="col-lg-6">{{ $r['emf_rate'] ?? '' }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Daily Program Distance</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', (float)($r['miles'] ?? 0)) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Total Daily Rate</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', (float)($r['rental'] ?? 0)) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Weekly Rate</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', (float)($r['rental'] ?? 0) * 7) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Monthly Rate</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', (float)($r['rental'] ?? 0) * 365 / 12) }}</div>
        </div>
        <legend>Usage Payment Breakdown</legend>
        @php
            $dp = (float) ($downpaymentPaid ?? 0);
            $writedownallocation = ((float)($calculation['write_down_allocation'] ?? 0)) * $dp / 100;
            $financeallocation = ((float)($calculation['finance_allocation'] ?? 0)) * $dp / 100;
            $maintenanceallocation = ((float)($calculation['maintenance_allocation'] ?? 0)) * $dp / 100;
            $dispositionfee = ((float)($calculation['disposition_fee'] ?? 0)) * $dp / 100;
        @endphp
        <div class="form-group">
            <label class="col-lg-6">Write Down Allocation</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', $writedownallocation) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Finance Allocation</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', $financeallocation) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Maintnenace Allocation</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', $maintenanceallocation) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">Disposition Fee Allocation</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', $dispositionfee) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6">DIA Fee</label>
            <div class="col-lg-6">{{ sprintf('%0.2f', (float)($totalDiaFee ?? 0)) }}</div>
        </div>
        <div class="form-group">
            <label class="col-lg-6 text-bold">Total</label>
            <div class="col-lg-6 text-bold">{{ sprintf('%0.2f', $writedownallocation + $financeallocation + $maintenanceallocation + $dispositionfee + (float)($totalDiaFee ?? 0)) }}</div>
        </div>
    </div>
@endif
