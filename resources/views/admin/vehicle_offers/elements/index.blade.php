@php
    $vehicleOffers ??= [];
    $limit ??= 50;
    $timezone ??= config('app.timezone', 'UTC')
@endphp


<div class="table-responsive">
    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th valign="top">
                    <input type="checkbox" id="selectAllChildCheckboxs" value="1">
                </th>
                <th valign="top" width='10%'>Vehicle#</th>
                <th valign="top">Vehicle </th>
                <th valign="top">Driver</th>
                <th valign="top">Driver Phone</th>
                <th valign="top">Rental</th>
                <th valign="top">Initial Fee</th>
                <th valign="top">Deposit</th>
                <th valign="top">Start Date</th>
                <th valign="top">Financing</th>
                <th valign="top">Status</th>
                <th valign="top" width='8%'>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($vehicleOffers as $vehicleOffer)
                <tr id="{{ data_get($vehicleOffer, 'id', '') }}">
                    <td>
                        <input type="checkbox" name="select[{{ data_get($vehicleOffer, 'id', '') }}]"
                            value="{{ data_get($vehicleOffer, 'id', '') }}" class="select-item">
                    </td>
                    <td>
                        {{ data_get($vehicleOffer, 'vehicle.vehicle_unique_id', '') }}
                    </td>
                    <td>
                        {{ data_get($vehicleOffer, 'vehicle.vehicle_name', '') }}
                    </td>
                    <td>
                        {{ trim(data_get($vehicleOffer, 'owner.first_name', '') . ' ' . data_get($vehicleOffer, 'owner.last_name', '')) }}
                    </td>
                    <td>
                        {{ data_get($vehicleOffer, 'driver_phone', '')}}
                    </td>
                    <td>
                        {{ number_format(data_get($vehicleOffer, 'day_rent', 0), 2) }}
                    </td>
                    <td>
                        {{ number_format(data_get($vehicleOffer, 'total_initial_fee', 0), 2) }}
                    </td>
                    <td>
                        {{ number_format(data_get($vehicleOffer, 'total_deposit_amt', 0), 2) }}
                    </td>
                    <td>
                        {{ \Carbon\Carbon::parse(data_get($vehicleOffer, 'start_datetime'))->timezone($timezone)->format('m/d/Y h:i A') }}
                    </td>
                    <td>
                        {{ $commonService->getVehicleFinancing(data_get($vehicleOffer, 'financing')) }}
                    </td>
                    <td>
                        @if (data_get($vehicleOffer, 'status') == 1)
                            Accepted
                        @elseif (data_get($vehicleOffer, 'status') == 2)
                            Canceled
                        @elseif (data_get($vehicleOffer, 'status') == 0)
                            New
                        @endif
                    </td>

                    <td>
                        @if(data_get($vehicleOffer, 'status') == 0)
                            <a href="/admin/vehicle_offers/cancel/{{ base64_encode(data_get($vehicleOffer, 'id', '')) }}"
                                onclick="return confirm('Are you sure you want to cancel it?')" title="Cancel">
                                <i class="glyphicon glyphicon-remove-circle"></i>
                            </a>
                            &nbsp;
                            <a href="/admin/vehicle_offers/add/{{ base64_encode(data_get($vehicleOffer, 'id', '')) }}"
                                title="Edit">
                                <i class="glyphicon glyphicon-edit"></i>
                            </a>
                            &nbsp;
                            <a href="/admin/vehicle_offers/delete/{{ base64_encode(data_get($vehicleOffer, 'id', '')) }}"
                                onclick="return confirm('Delete this offer?')" title="Delete">
                                <i class="glyphicon glyphicon-trash"></i>
                            </a>
                        @endif
                        @if(data_get($vehicleOffer, 'status') == 1)
                            <a href="/admin/vehicle_offers/duplicate/{{ base64_encode(data_get($vehicleOffer, 'id', '')) }}"
                                onclick="return confirm('Are you sure you want to duplicate it?')" title="Duplicate">
                                <i class="icon-copy3"></i>
                            </a>
                        @endif
                        &nbsp;
                        <a href="/admin/vehicle_offers/view/{{ base64_encode(data_get($vehicleOffer, 'id', '')) }}"
                            title="View">
                            <i class="glyphicon glyphicon-zoom-in"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" align="center">No offers found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.dispacher.paging_box', ['paginator' => $vehicleOffers, 'limit' => $limit])