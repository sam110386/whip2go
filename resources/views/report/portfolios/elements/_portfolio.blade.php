<table width="100%" id="portfolio" cellpadding="0" cellspacing="0" class="table  table-responsive panel">
    <thead>
        <tr>
            <th style="text-align:center;"> Vehicle</th>
            <th style="text-align:center;"> Rental Days</th>
            <th style="text-align:center;"> Fleet Days</th>
            <th style="text-align:center;"> Distance</th>
            <th style="text-align:center;"> Usage</th>
            <th style="text-align:center;"> Extra Usage</th>
            <th style="text-align:center;"> Total Usage</th>
            <th style="text-align:center;"> W/D Allocation</th>
            <th style="text-align:center;"> Finanace Allocation</th>
            <th style="text-align:center;"> Maintenance Allocation</th>
            <th style="text-align:center;"> DIA Fee</th>
            <th style="text-align:center;"> Disposition</th>
            <th style="text-align:center;"> Total Usage</th>
            <th style="text-align:center;"> Depreciation</th>
            <th style="text-align:center;"> Finance Cost</th>
            <th style="text-align:center;"> Body Damage</th>
            <th style="text-align:center;"> Mech.Damage</th>
            <th style="text-align:center;"> Maintenance</th>
            <th style="text-align:center;"> Tolls</th>
            <th style="text-align:center;"> UnCollected Insurance</th>
            <th style="text-align:center;"> DIA Fee</th>
            <th style="text-align:center;"> Misc fee</th>
            <th style="text-align:center;" class="danger"> Total</th>
            <th style="text-align:center;" class="bg-slate-600"> Profit</th>
            <th style="text-align:center;"> Vehicle Cost</th>
            <th style="text-align:center;" class="bg-slate-600"> Ending Cost</th>
        </tr>
    </thead>
    <tbody>
        @php
            $date_from ??= null;
            $date_to ??= null;
            $taxIncluded ??= false;
            $rev_share ??= 0;
            $rental_rev ??= 0;
            $totaldays = $mileage = $totalrent = $totalTax = $deposit = $write_down_allocation = $extra_mileage_fee = $lateness_fee = $toll_fee = $financecost = $msrptotal = 0.00;
            $depreciation = $bodydamage = $mechdamage = $maintenance = $toll = $profilttotal = $endingCosttotal = $totalInsurance = $totalDiaFee = $totalRentalDiaPart = $disposition_fee = $totalCalculatedDiaFee = $totalMiscFee = 0.00;
            $finance_allocation = 0.00;
            $maintenance_allocation = 0.00;
        @endphp

        @foreach ($vehicles as $vehicle)
            @php
                $Earnnings = \App\Helpers\Legacy\ReportHelper::getVehiclePortfolio($vehicle->id);
                $expenses = \App\Helpers\Legacy\ReportHelper::getVehicleExpenses($vehicle->id, $date_from, $date_to);
                $VehicleDepriciationData = \App\Helpers\Legacy\ReportHelper::getVehicleDepriciationReport($vehicle->id);
                $totaldays += (int) ($Earnnings['totaldays'] ?? 0);
                $mileage += (int) ($Earnnings['miles'] ?? 0);
                $rent = sprintf('%0.2f', (($Earnnings['total_collected'] ?? 0) - ($Earnnings['emf_collected'] ?? 0) - ($Earnnings['total_tax_collected'] ?? 0)));
                $totalrent += (float) $rent;
                $emf = sprintf('%0.2f', ($Earnnings['emf_collected'] ?? 0));
                $extra_mileage_fee += (float) $emf;
                $earning = ((float) $rent + (float) $emf);
                $write_down_allocation += (float) ($Earnnings['write_down_allocation'] ?? 0);
                $finance_allocation += (float) ($Earnnings['finance_allocation'] ?? 0);
                $maintenance_allocation += (float) ($Earnnings['maintenance_allocation'] ?? 0);
                $taxAmount = $taxIncluded ? 0 : ($Earnnings['tax'] ?? 0);
                $calculatedDiaFee = sprintf(
                    '%0.2f',
                    (($Earnnings['total_billed'] ?? 0) - $taxAmount) * (100 - $rental_rev) / 100
                );
                $totalCalculatedDiaFee += (float) $calculatedDiaFee;
                $disposition_fee += (float) ($Earnnings['disposition_fee'] ?? 0);
                $totalRentalDia = sprintf(
                    '%0.2f',
                    (
                        ($Earnnings['write_down_allocation'] ?? 0)
                        + ($Earnnings['finance_allocation'] ?? 0)
                        + ($Earnnings['maintenance_allocation'] ?? 0)
                        + (float) $calculatedDiaFee
                        + ($Earnnings['disposition_fee'] ?? 0)
                    )
                );
                $totalRentalDiaPart += (float) $totalRentalDia;
                $depreciation += (float) ($VehicleDepriciationData['depreciation'] ?? 0);
                $financecost += (float) ($VehicleDepriciationData['financing'] ?? 0);
                $bodydamage += (float) ($expenses['bodydamage'] ?? 0);
                $mechdamage += (float) ($expenses['mechdamage'] ?? 0);
                $maintenance += (float) ($expenses['maintenance'] ?? 0);
                $toll += ($expenses['toll'] ?? 0);
                $uncollectedInsu = sprintf(
                    '%0.2f',
                    (
                        ($Earnnings['calculated_insurance'] ?? 0)
                        - ($Earnnings['insurance_by_dealer'] ?? 0)
                        - ($Earnnings['insurance_by_renter'] ?? 0)
                    )
                );
                $totalInsurance += (float) $uncollectedInsu;
                $diaFee = sprintf(
                    '%0.2f',
                    ($earning * (100 - $rev_share) / 100)
                );
                $totalDiaFee += (float) $diaFee;
                $totalMiscFee += (float) ($Earnnings['stripe_fee'] ?? 0);
                $totalexp = sprintf(
                    '%0.2f',
                    (
                        ($VehicleDepriciationData['financing'] ?? 0)
                        + ($VehicleDepriciationData['depreciation'] ?? 0)
                        + ($expenses['bodydamage'] ?? 0)
                        + ($expenses['mechdamage'] ?? 0)
                        + ($expenses['maintenance'] ?? 0)
                        + ($expenses['toll'] ?? 0)
                        + (float) $uncollectedInsu
                        + (float) $diaFee
                        + ($Earnnings['stripe_fee'] ?? 0)
                    )
                );
                $profilttotal += (float) ($earning - (float) $totalexp);
                $msrp = ($vehicle['vehicleCostInclRecon'] ?? 0);
                $msrptotal += (float) $msrp;
                $endingCosttotal += (float) ($msrp - ($earning - (float) $totalexp));
            @endphp

            <tr id="{{ $vehicle->id }}">
                <td>
                    {{ $vehicle['vehicle_name'] ?? '' }}
                </td>
                <td>
                    {{ $Earnnings['totaldays'] ?? '' }}
                </td>
                <td>
                    {{ $VehicleDepriciationData['fleet_days'] ?? '' }}
                </td>
                <td>
                    {{ $Earnnings['miles'] ?? '' }}
                </td>
                <td>
                    {{ $rent }}
                </td>
                <td>
                    {{ $emf }}
                </td>
                <td class="danger">
                    {{ $earning }}
                </td>
                <td>
                    {{ sprintf('%0.2f', ($Earnnings['write_down_allocation'] ?? 0)) }}
                </td>
                <td>
                    {{ sprintf('%0.2f', ($Earnnings['finance_allocation'] ?? 0)) }}
                </td>
                <td>
                    {{ sprintf('%0.2f', ($Earnnings['maintenance_allocation'] ?? 0)) }}
                </td>
                <td>
                    {{ $calculatedDiaFee }}
                </td>
                <td>
                    {{ sprintf('%0.2f', ($Earnnings['disposition_fee'] ?? 0)) }}
                </td>
                <td class="danger">
                    {{ $totalRentalDia }}
                </td>
                <td>
                    {{ $VehicleDepriciationData['depreciation'] ?? '' }}
                </td>
                <td>
                    {{ $VehicleDepriciationData['financing'] ?? '' }}
                </td>
                <td>
                    {{ $expenses['bodydamage'] ?? '' }}
                </td>
                <td>
                    {{ $expenses['mechdamage'] ?? '' }}
                </td>
                <td>
                    {{ $expenses['maintenance'] ?? '' }}
                </td>
                <td>
                    {{ $expenses['toll'] ?? '' }}
                </td>
                <td>
                    {{ $uncollectedInsu }}
                </td>
                <td>
                    {{ $diaFee }}
                </td>
                <td>
                    {{ sprintf('%0.2f', ($Earnnings['stripe_fee'] ?? 0)) }}
                </td>
                <td class="danger">
                    {{ $totalexp }}
                </td>
                <td class="bg-slate-600">
                    {{ $earning - (float) $totalexp }}
                </td>
                <td>
                    {{ $msrp }}
                </td>
                <td class="bg-slate-600">
                    {{ $msrp - ($earning - (float) $totalexp) }}
                </td>
            </tr>
        @endforeach

        <tr style="background:#ccc;">
            <th>
                {{'Total'}}
            </th>
            <td>
                {{ $totaldays }}
            </td>
            <td></td>
            <td>
                {{ $mileage }}
            </td>
            <td>
                {{ $totalrent }}
            </td>
            <td>
                {{ $extra_mileage_fee }}
            </td>
            <td class="danger">
                {{ sprintf('%0.2f', ($totalrent + $extra_mileage_fee)) }}
            </td>
            <td>
                {{ sprintf('%0.2f', $write_down_allocation) }}
            </td>
            <td>
                {{ sprintf('%0.2f', $finance_allocation) }}
            </td>
            <td>
                {{ sprintf('%0.2f', $maintenance_allocation) }}
            </td>
            <td>
                {{ sprintf('%0.2f', $totalDiaFee) }}
            </td>
            <td>
                {{ sprintf('%0.2f', $disposition_fee) }}
            </td>
            <td class="danger">
                {{ sprintf('%0.2f', $totalRentalDiaPart) }}
            </td>
            <td>
                {{ sprintf('%0.2f', $depreciation) }}
            </td>
            <td>
                {{ sprintf('%0.2f', $financecost) }}
            </td>
            <td>
                {{ $bodydamage }}
            </td>
            <td>
                {{ $mechdamage }}
            </td>
            <td>
                {{ $maintenance }}
            </td>
            <td>
                {{ $toll }}
            </td>
            <td>
                {{ sprintf('%0.2f', $totalInsurance) }}
            </td>
            <td>
                {{ sprintf('%0.2f', $totalDiaFee) }}
            </td>
            <td>
                {{ sprintf('%0.2f', $totalMiscFee) }}
            </td>
            <td class="danger">
                {{ sprintf('%0.2f', ($financecost + $disposition_fee + $depreciation + $bodydamage + $mechdamage + $maintenance + $toll + $totalInsurance)) }}
            </td>
            <td class="bg-slate-600">
                {{ sprintf('%0.2f', $profilttotal) }}
            </td>
            <td>
                {{ sprintf('%0.2f', $msrptotal) }}
            </td>
            <td class="bg-slate-600">
                {{ sprintf('%0.2f', $endingCosttotal) }}
            </td>
        </tr>
    </tbody>
</table>