<div class="table-responsive">
    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['field' => 'select_all', 'title' => '<input type="checkbox" id="selectAllChildCheckboxs" value="1">', 'sortable' => false, 'html' => true],
                    ['field' => 'vehicle_unique_id', 'title' => 'Vehicle#', 'sortable' => false],
                    ['field' => 'vehicle_name', 'title' => 'Vehicle', 'sortable' => false],
                    ['field' => 'renter_first_name', 'title' => 'Driver', 'sortable' => false],
                    ['field' => 'driver_phone', 'title' => 'Driver Phone', 'sortable' => false],
                    ['field' => 'day_rent', 'title' => 'Rental', 'sortable' => false],
                    ['field' => 'total_initial_fee', 'title' => 'Initial Fee', 'sortable' => false],
                    ['field' => 'total_deposit_amt', 'title' => 'Deposit', 'sortable' => false],
                    ['field' => 'start_datetime', 'title' => 'Start Date', 'sortable' => false],
                    ['field' => 'financing', 'title' => 'Financing', 'sortable' => false],
                    ['field' => 'status', 'title' => 'Status', 'sortable' => false],
                    ['field' => 'actions', 'title' => 'Actions', 'sortable' => false]
                ]])
            </tr>
        </thead>
        <tbody>
            @forelse ($offers as $o)
                <tr>
                    <td><input type="checkbox" name="select[{{ $o->id }}]" value="{{ $o->id }}" class="select-item"></td>
                    <td>{{ $o->vehicle_unique_id }}</td>
                    <td>{{ $o->vehicle_name }}</td>
                    <td>{{ trim(($o->renter_first_name ?? '') . ' ' . ($o->renter_last_name ?? '')) }}</td>
                    <td>{{ $o->driver_phone }}</td>
                    <td>{{ number_format((float) ($o->day_rent ?? 0), 2) }}</td>
                    <td>{{ number_format((float) ($o->total_initial_fee ?? 0), 2) }}</td>
                    <td>{{ number_format((float) ($o->total_deposit_amt ?? 0), 2) }}</td>
                    <td>{{ $o->start_datetime }}</td>
                    <td>{{ $getFinancing($o->financing) }}</td>
                    <td>
                        @if ($o->status == 1) Accepted
                        @elseif ($o->status == 2) Canceled
                        @elseif ($o->status == 0) New
                        @endif
                    </td>
                    
                    <td>
                        @if($o->status == 0)
                            <a href="/admin/vehicle_offers/cancel/{{ base64_encode((string) $o->id) }}" onclick="return confirm('Are you sure you want to cancel it?')" title="Cancel">
                                <i class="glyphicon glyphicon-remove-circle"></i>
                            </a>
                            &nbsp;
                            <a href="/admin/vehicle_offers/add/{{ base64_encode((string) $o->id) }}" title="Edit">
                                <i class="glyphicon glyphicon-edit"></i>
                            </a>
                            &nbsp;
                            <a href="/admin/vehicle_offers/delete/{{ base64_encode((string) $o->id) }}" onclick="return confirm('Delete this offer?')" title="Delete">
                                <i class="glyphicon glyphicon-trash"></i>
                            </a>
                        @endif
                        @if($o->status == 1)
                            <a href="/admin/vehicle_offers/duplicate/{{ base64_encode((string) $o->id) }}" onclick="return confirm('Are you sure you want to duplicate it?')" title="Duplicate">
                                <i class="icon-copy3"></i>
                            </a>
                        @endif
                        &nbsp;
                        <a href="/admin/vehicle_offers/view/{{ base64_encode((string) $o->id) }}" title="View">
                            <i class="glyphicon glyphicon-zoom-in"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" align="center">No offers found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('partials.dispacher.paging_box', ['paginator' => $offers, 'limit' => $limit])
