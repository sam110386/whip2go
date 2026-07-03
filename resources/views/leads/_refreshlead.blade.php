@php
    $lead ??= collect();
    $user ??= collect();
    $vehicleReservation ??= collect();
    $intercomContact ??= [];
    $leadid ??= '';
    $message ??= '';
    $error ??= false;
@endphp

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <div class="panel">
        <div class="panel-body">

            @if($error)
                <h5 class="text-danger">{{ $message }}</h5>
            @endif

            @if($lead)
                <legend class="text-semibold">Lead Details</legend>

                <form class="form-horizontal">
                    <div class="form-group">
                        <label>First Name:</label>
                        {{ e(data_get($lead, 'first_name', '')) }}
                    </div>

                    <div class="form-group">
                        <label>Last Name:</label>
                        {{ e(data_get($lead, 'last_name', '')) }}
                    </div>

                    <div class="form-group">
                        <label>Email:</label>
                        {{ data_get($lead, 'email', '') }}
                    </div>

                    <div class="form-group">
                        <label>Phone:</label>
                        {{ data_get($lead, 'phone', '') }}
                    </div>

                    @if($user)
                        <legend class="text-semibold">Registered As</legend>

                        <div class="form-group">
                            <label>User Name:</label>
                            {{ e(data_get($user, 'first_name', '') . ' ' . data_get($user, 'last_name', '')) }}
                        </div>

                        <div class="form-group">
                            <label>User Email:</label>
                            {{ data_get($user, 'email', '')}}
                        </div>

                    @endif

                    @if(!empty($intercomContact))
                        <legend class="text-semibold">Intercom Contact Details</legend>

                        <div class="form-group">
                            <label>Intercom ID:</label>
                            {{ e(data_get($intercomContact, 'id', '')) }}
                        </div>

                        <div class="form-group">
                            <label>Created At:</label>
                            {{ date('Y-m-d H:i:s', data_get($intercomContact, 'created_at')) }}
                        </div>

                        <div class="form-group">
                            <label>Last Seen At:</label>
                            {{ date('Y-m-d H:i:s', data_get($intercomContact, 'last_seen_at')) }}
                        </div>

                    @endif

                    <legend class="text-semibold">Booking Details</legend>

                    <div class="form-group">
                        <label>Pending Booking:</label>
                        {{ (data_get($vehicleReservation, 'status', '') != 1) ? 'Yes' : 'No' }}
                    </div>

                    @if(data_get($vehicleReservation, 'status', '') == 2)
                        <div class="form-group">
                            <label>Cancel Note:</label>
                            {{ data_get($vehicleReservation, 'cancel_note', '')}}
                        </div>
                    @endif

                    <div class="form-group">
                        <label>Active Booking:</label>
                        {{ (data_get($vehicleReservation, 'status', '') == 1) ? 'Yes' : 'No' }}
                    </div>

                </form>
            @endif
        </div>
    </div>
</div>