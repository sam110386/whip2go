<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <div class="panel">
        <div class="panel-body">
            @if($error)
                <h5 class="text-danger">{{ $message }}</h5>
            @endif
            @if(!empty($lead))
            <legend class="text-semibold">Lead Details</legend>
            <form class="form-horizontal">
                <div class="form-group">
                    <label>First Name:</label>
                    {{ e($lead->first_name ?? '') }}
                </div>
                <div class="form-group">
                    <label>Last Name:</label>
                    {{ e($lead->last_name ?? '') }}
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    {{ $lead->email ?? '' }}
                </div>
                <div class="form-group">
                    <label>Phone:</label>
                    {{ $lead->phone ?? '' }}
                </div>
                @if(!empty($user))
                    <legend class="text-semibold">Registered As</legend>
                    <div class="form-group">
                        <label>User Name:</label>
                        {{ e($user->first_name . ' ' . $user->last_name) }}
                    </div>
                    <div class="form-group">
                        <label>User Email:</label>
                        {{ $user->email }}
                    </div>
                @endif
                @if(!empty($intecomContact) && is_object($intecomContact))
                    <legend class="text-semibold">Intercom Contact Details</legend>
                    <div class="form-group">
                        <label>Intercom ID:</label>
                        {{ e($intecomContact->id) }}
                    </div>
                    <div class="form-group">
                        <label>Created At:</label>
                        {{ date('Y-m-d H:i:s', $intecomContact->created_at) }}
                    </div>
                    <div class="form-group">
                        <label>Last Seen At:</label>
                        {{ date('Y-m-d H:i:s', $intecomContact->last_seen_at) }}
                    </div>
                @endif
                <legend class="text-semibold">Booking Details</legend>
                <div class="form-group">
                    <label>Pending Booking:</label>
                    {{ (!empty($VehicleReservation) && $VehicleReservation->status != 1) ? 'Yes' : 'No' }}
                </div>
                @if(!empty($VehicleReservation) && $VehicleReservation->status == 2)
                    <div class="form-group">
                        <label>Cancel Note:</label>
                        {{ $VehicleReservation->cancel_note ?? '' }}
                    </div>
                @endif
                <div class="form-group">
                    <label>Active Booking:</label>
                    {{ (!empty($VehicleReservation) && $VehicleReservation->status == 1) ? 'Yes' : 'No' }}
                </div>
            </form>
            @endif
        </div>
    </div>
</div>
