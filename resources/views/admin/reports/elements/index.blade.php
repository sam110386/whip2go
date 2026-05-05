@if(isset($reportlists) && is_object($reportlists) && method_exists($reportlists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit ?? 50, 'positon' => 'top'])
@endif

<table class="table table-responsive" style="width:100%;">
    <thead>
        <tr>
            @include('partials.dispacher.sortable_header', [
                'columns' => [
                    ['title' => '<input type="checkbox" id="selectAllChildCheckboxs" value="1">', 'sortable' => false, 'html' => true],
                    ['title' => 'Booking#', 'field' => 'increment_id'],
                    ['title' => 'Status', 'sortable' => false],
                    ['title' => 'Vehicle', 'sortable' => false],
                    ['title' => 'Start Time', 'field' => 'start_datetime'],
                    ['title' => 'End Time', 'field' => 'end_datetime'],
                    ['title' => 'Duration', 'sortable' => false],
                    ['title' => 'Customer', 'sortable' => false],
                    ['title' => 'Distance', 'sortable' => false],
                    ['title' => 'Usage', 'sortable' => false],
                    ['title' => 'Insurance', 'sortable' => false],
                    ['title' => 'Violation', 'sortable' => false],
                    ['title' => 'Documents', 'sortable' => false, 'style' => "width:80px;"]
                ]
            ]) 
        </tr>
    </thead>
    <tbody>
        @forelse($reportlists as $r)
            @php
                $autorenewedChilds = [];
                $loadsubbooking = $openTripDetails = "openTripDetails('" . base64_encode($r->id) . "')";

                if ($r->auto_renew || $r->pto) {
                    $loadsubbooking = "loadsubbooking('{$r->id}')";
                    $openTripDetails = "openCombinedBookingDetails('" . base64_encode($r->id) . "')";
                    $autorenewedChilds = $commonService->getChildBookingEndDate($r->id);
                }

            @endphp
            
        <tr id="tr_{{ $r->id  }}">
            <td>
                <input type="checkbox" name="select[{{ $r->id }}]" value="{{ $r->id }}" class="select-item" id="select1" style = "border:0;">
            </td>

            @if ($r->auto_renew )
                <td onclick="{{$loadsubbooking}}">
                    <i class="icon-forward position-left text-warning-400"></i>
                    {{ $r->increment_id ?? '' }}
                </td>
            @else
                <td onclick="{{ $openTripDetails }}">
                    {{ $r->increment_id ?? '' }}
                </td>
            @endif

            <td onclick="{{ $openTripDetails }}">
                @if(!$r->auto_renew) 
                    @if ($r->status == 3) 
                        {{"Completed"}}
                    @elseif ($r->status == 2)
                        {{"Canceled"}}
                    @else 
                        {{"Incomplete"}}
                    @endif
                @endif
            </td>

            <td onclick="inspektScanReport({{$r->id}})" class="">
                <span class="btn-link text-blue">
                    {{$r->vehicle_name}}
                </span>
            </td>

            <td onclick="{{ $openTripDetails }}">
                {{ \Carbon\Carbon::parse($r->start_datetime)->timezone($r->timezone)->format('m/d/Y h:i A') }}
            </td>
            <td onclick="{{ $openTripDetails }}">
                {{ ($r->auto_renew && !empty($autorenewedChilds)) ? \Carbon\Carbon::parse($autorenewedChilds['end_datetime'])->timezone($r->timezone)->format('m/d/Y h:i A') : \Carbon\Carbon::parse($r->end_datetime)->timezone($r->timezone)->format('m/d/Y h:i A') }}
            </td>

            <td onclick="{{ $openTripDetails }}">
                {{ $r->auto_renew ? $commonService->days_between_dates($r->start_datetime, $autorenewedChilds['end_datetime']) : $commonService->days_between_dates($r->start_datetime, $r->end_datetime) }}
            </td>

            <td onclick="{{ $openTripDetails }}">
                {{ trim(($r->renter_first_name ?? '') . ' ' . ($r->renter_last_name ?? '')) }}
            </td>

            <td onclick="{{ $openTripDetails }}">
                {{ ($r->auto_renew && !empty($autorenewedChilds)) ? $autorenewedChilds['mileage'] : ($r->status == 3 ? $r->end_odometer - $r->start_odometer : 0)}}
            </td>

            <td onclick="{{ $openTripDetails }}">
                {{ ($r->auto_renew && !empty($autorenewedChilds)) ? $autorenewedChilds['paid_amount'] : $r->rent + $r->tax + $r->initial_fee + $r->extra_mileage_fee + $r->damage_fee + $r->lateness_fee + $r->uncleanness_fee}}
            </td>

            <td onclick="{{ $openTripDetails }}">
                {{ ($r->auto_renew && !empty($autorenewedChilds)) ? $autorenewedChilds['insurance'] : $r->insurance_amt + $r->dia_insu}}
            </td>
            <td onclick="{{ $openTripDetails }}">
                {{ ($r->auto_renew && !empty($autorenewedChilds)) ? $autorenewedChilds['toll'] :  $r->toll }}
            </td>

            <td>
                @if($r->status == 3) 
                    <a href="javascript:void(0)" onclick="reviewimages('{{ base64_encode($r->id) }}')" title="Review Images">
                        <i class='icon-clipboard3'></i>
                    </a>
                @endif
                <a href="javascript:void(0)" onclick="return downloadBookingDoc('{{ base64_encode($r->id) }}')" title="Download Doc">
                    <i class=" icon-file-pdf"></i>
                </a>
                <a href="javascript:void(0)" onclick="return inspektScanReport('{{ $r->id }}')" title="Inspeck Scan report">
                   <i class="icon-magazine"></i>
                </a>
            </td>
        </tr>
        @empty
            <tr>
                <td heigth="6" colspan="13">
                    No rows found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if(isset($reportlists) && is_object($reportlists) && method_exists($reportlists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit ?? 50])
@endif

