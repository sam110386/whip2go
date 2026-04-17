@php
    /** @var \App\Services\Legacy\Report\PortfolioService $portfolioSvc */
    $portfolioSvc = app(\App\Services\Legacy\Report\PortfolioService::class);
    $dateFrom = $date_from ?? null;
    $dateTo = $date_to ?? null;
    $taxIncluded = $taxIncluded ?? false;
    $rev_share = $rev_share ?? 0;
    $rental_rev = $rental_rev ?? 0;
@endphp
<table width="100%" id="portfolio" cellpadding="0" cellspacing="0" class="table  table-responsive panel">
    <thead>
        <tr>
            <th>{{ 'Vehicle' }}</th>
            <th>{{ 'Rental Days' }}</th>
            <th>{{ 'Fleet Days' }}</th>
            <th>{{ 'Distance' }}</th>
            <th>{{ 'Usage' }}</th>
            <th>{{ 'Extra Usage' }}</th>
            <th>{{ 'Total Usage' }}</th>
            <th>{{ 'W/D Allocation' }}</th>
            <th>{{ 'Finanace Allocation' }}</th>
            <th>{{ 'Maintenance Allocation' }}</th>
            <th>{{ 'DIA Fee' }}</th>
            <th>{{ 'Disposition' }}</th>
            <th>{{ 'Total Usage' }}</th>
            <th>{{ 'Depreciation' }}</th>
            <th>{{ 'Finance Cost' }}</th>
            <th>{{ 'Body Damage' }}</th>
            <th>{{ 'Mech.Damage' }}</th>
            <th>{{ 'Maintenance' }}</th>
            <th>{{ 'Tolls' }}</th>
            <th>{{ 'UnCollected Insurance' }}</th>
            <th>{{ 'DIA Fee' }}</th>
            <th>{{ 'Misc fee' }}</th>
            <th class="danger">{{ 'Total' }}</th>
            <th class="bg-slate-600">{{ 'Profit' }}</th>
            <th>{{ 'Vehicle Cost' }}</th>
            <th class="bg-slate-600">{{ 'Ending Cost' }}</th>
        </tr>
    </thead>
    <tbody>
        @php
            $totalRow = '';
            $finalRow = '';
            $totaldays = $mileage = $totalrent = $totalTax = $deposit = $write_down_allocation = $extra_mileage_fee = $lateness_fee = $toll_fee = $financecost = $msrptotal = 0.00;
            $depreciation = $bodydamage = $mechdamage = $maintenance = $toll = $profilttotal = $endingCosttotal = $totalInsurance = $totalDiaFee = $totalRentalDiaPart = $disposition_fee = $totalCalculatedDiaFee = $totalMiscFee = 0.00;
            $finance_allocation = 0.00;
            $maintenance_allocation = 0.00;
            foreach ($vehicles as $vehicle) {
                $regularRow = '<tr id="' . ($vehicle['Vehicle']['id'] ?? '') . '">';
                $Earnnings = $portfolioSvc->getVehiclePortfolio($vehicle['Vehicle']['id']);
                $expenses = $portfolioSvc->getVehicleExpenses($vehicle['Vehicle']['id'], $dateFrom, $dateTo);
                $VehicleDepriciationData = $portfolioSvc->getVehicleDepriciationReport($vehicle['Vehicle']['id']);
                $regularRow .= '<td>' . e($vehicle['Vehicle']['vehicle_name'] ?? '') . '</td>';
                $regularRow .= '<td>' . ($Earnnings['totaldays'] ?? '');
                $totaldays += (int)($Earnnings['totaldays'] ?? 0);
                $regularRow .= '</td>';
                $regularRow .= '<td>' . ($VehicleDepriciationData['fleet_days'] ?? '') . '</td>';
                $regularRow .= '<td>' . ($Earnnings['miles'] ?? '');
                $mileage += (int)($Earnnings['miles'] ?? 0);
                $regularRow .= '</td>';
                $rent = sprintf('%0.2f', (($Earnnings['total_collected'] ?? 0) - ($Earnnings['emf_collected'] ?? 0) - ($Earnnings['total_tax_collected'] ?? 0)));
                $regularRow .= '<td>' . $rent . '</td>';
                $totalrent += (float) $rent;
                $emf = sprintf('%0.2f', ($Earnnings['emf_collected'] ?? 0));
                $regularRow .= '<td>' . $emf . '</td>';
                $extra_mileage_fee += (float) $emf;
                $earning = ((float) $rent + (float) $emf);
                $regularRow .= '<td class="danger">' . $earning . '</td>';
                $regularRow .= '<td>' . sprintf('%0.2f', ($Earnnings['write_down_allocation'] ?? 0)) . '</td>';
                $write_down_allocation += (float)($Earnnings['write_down_allocation'] ?? 0);
                $regularRow .= '<td>' . sprintf('%0.2f', ($Earnnings['finance_allocation'] ?? 0)) . '</td>';
                $finance_allocation += (float)($Earnnings['finance_allocation'] ?? 0);
                $regularRow .= '<td>' . sprintf('%0.2f', ($Earnnings['maintenance_allocation'] ?? 0)) . '</td>';
                $maintenance_allocation += (float)($Earnnings['maintenance_allocation'] ?? 0);
                if ($taxIncluded) {
                    $calculatedDiaFee = sprintf('%0.2f', (($Earnnings['total_billed'] ?? 0) * (100 - $rental_rev) / 100));
                } else {
                    $calculatedDiaFee = sprintf('%0.2f', ((($Earnnings['total_billed'] ?? 0) - ($Earnnings['tax'] ?? 0)) * (100 - $rental_rev) / 100));
                }
                $totalCalculatedDiaFee += (float) $calculatedDiaFee;
                $regularRow .= '<td>' . $calculatedDiaFee . '</td>';
                $regularRow .= '<td>' . sprintf('%0.2f', ($Earnnings['disposition_fee'] ?? 0)) . '</td>';
                $disposition_fee += (float)($Earnnings['disposition_fee'] ?? 0);
                $totalRentalDia = sprintf(
                    '%0.2f',
                    (($Earnnings['write_down_allocation'] ?? 0) + ($Earnnings['finance_allocation'] ?? 0) + ($Earnnings['maintenance_allocation'] ?? 0) + (float) $calculatedDiaFee + ($Earnnings['disposition_fee'] ?? 0))
                );
                $regularRow .= '<td class="danger">' . $totalRentalDia . '</td>';
                $totalRentalDiaPart += (float) $totalRentalDia;
                $regularRow .= '<td>' . ($VehicleDepriciationData['depreciation'] ?? '') . '</td>';
                $depreciation += (float)($VehicleDepriciationData['depreciation'] ?? 0);
                $regularRow .= '<td>' . ($VehicleDepriciationData['financing'] ?? '') . '</td>';
                $financecost += (float)($VehicleDepriciationData['financing'] ?? 0);
                $regularRow .= '<td>' . ($expenses['bodydamage'] ?? '') . '</td>';
                $bodydamage += (float)($expenses['bodydamage'] ?? 0);
                $regularRow .= '<td>' . ($expenses['mechdamage'] ?? '') . '</td>';
                $mechdamage += (float)($expenses['mechdamage'] ?? 0);
                $regularRow .= '<td>' . ($expenses['maintenance'] ?? '') . '</td>';
                $maintenance += (float)($expenses['maintenance'] ?? 0);
                $regularRow .= '<td>' . ($expenses['toll'] ?? '') . '</td>';
                $toll += ($expenses['toll'] ?? 0);
                $uncollectedInsu = sprintf('%0.2f', (($Earnnings['calculated_insurance'] ?? 0) - ($Earnnings['insurance_by_dealer'] ?? 0) - ($Earnnings['insurance_by_renter'] ?? 0)));
                $regularRow .= '<td>' . $uncollectedInsu . '</td>';
                $totalInsurance += (float) $uncollectedInsu;
                $diaFee = sprintf('%0.2f', ($earning * (100 - $rev_share) / 100));
                $regularRow .= '<td>' . $diaFee . '</td>';
                $totalDiaFee += (float) $diaFee;
                $regularRow .= '<td>' . sprintf('%0.2f', ($Earnnings['stripe_fee'] ?? 0)) . '</td>';
                $totalMiscFee += (float)($Earnnings['stripe_fee'] ?? 0);
                $totalexp = sprintf(
                    '%0.2f',
                    ($VehicleDepriciationData['financing'] ?? 0)
                    + ($VehicleDepriciationData['depreciation'] ?? 0)
                    + ($expenses['bodydamage'] ?? 0)
                    + ($expenses['mechdamage'] ?? 0)
                    + ($expenses['maintenance'] ?? 0)
                    + ($expenses['toll'] ?? 0)
                    + (float) $uncollectedInsu
                    + (float) $diaFee
                    + ($Earnnings['stripe_fee'] ?? 0)
                );
                $regularRow .= '<td class="danger">' . $totalexp . '</td>';
                $regularRow .= '<td class="bg-slate-600">' . ($earning - (float) $totalexp) . '</td>';
                $profilttotal += (float) ($earning - (float) $totalexp);
                $msrp = ($vehicle['Vehicle']['vehicleCostInclRecon'] ?? 0);
                $regularRow .= '<td>' . $msrp . '</td>';
                $msrptotal += (float) $msrp;
                $regularRow .= '<td class="bg-slate-600">' . ($msrp - ($earning - (float) $totalexp)) . '</td>';
                $endingCosttotal += (float) ($msrp - ($earning - (float) $totalexp));
                echo $regularRow . '</tr>';
            }
            $totalRow .= '<tr style="background:#ccc;">';
            $totalRow .= '<th>Total</th>';
            $totalRow .= '<td>' . $totaldays . '</td>';
            $totalRow .= '<td></td>';
            $totalRow .= '<td>' . $mileage . '</td>';
            $totalRow .= '<td>' . $totalrent . '</td>';
            $totalRow .= '<td>' . $extra_mileage_fee . '</td>';
            $totalRow .= '<td class="danger">' . sprintf('%0.2f', ($totalrent + $extra_mileage_fee)) . '</td>';
            $totalRow .= '<td>' . sprintf('%0.2f', $write_down_allocation) . '</td>';
            $totalRow .= '<td>' . sprintf('%0.2f', $finance_allocation) . '</td>';
            $totalRow .= '<td>' . sprintf('%0.2f', $maintenance_allocation) . '</td>';
            $totalRow .= '<td>' . sprintf('%0.2f', $totalDiaFee) . '</td>';
            $totalRow .= '<td>' . sprintf('%0.2f', $disposition_fee) . '</td>';
            $totalRow .= '<td class="danger">' . sprintf('%0.2f', $totalRentalDiaPart) . '</td>';
            $totalRow .= '<td>' . sprintf('%0.2f', $depreciation) . '</td>';
            $totalRow .= '<td>' . sprintf('%0.2f', $financecost) . '</td>';
            $totalRow .= '<td>' . $bodydamage . '</td>';
            $totalRow .= '<td>' . $mechdamage . '</td>';
            $totalRow .= '<td>' . $maintenance . '</td>';
            $totalRow .= '<td>' . $toll . '</td>';
            $totalRow .= '<td>' . sprintf('%0.2f', $totalInsurance) . '</td>';
            $totalRow .= '<td>' . sprintf('%0.2f', $totalDiaFee) . '</td>';
            $totalRow .= '<td>' . sprintf('%0.2f', $totalMiscFee) . '</td>';
            $totalRow .= '<td class="danger">' . sprintf('%0.2f', ($financecost + $disposition_fee + $depreciation + $bodydamage + $mechdamage + $maintenance + $toll + $totalInsurance)) . '</td>';
            $totalRow .= '<td class="bg-slate-600">' . sprintf('%0.2f', $profilttotal) . '</td>';
            $totalRow .= '<td>' . sprintf('%0.2f', $msrptotal) . '</td>';
            $totalRow .= '<td class="bg-slate-600">' . sprintf('%0.2f', $endingCosttotal) . '</td>';
            $totalRow .= '</tr>';
        @endphp
        {!! $totalRow !!}
    </tbody>
</table>
