<table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
    <thead>
        <tr>
            <th style="width:5px;">#</th>
            <th style="width:5px;">Amount</th>
            <th style="width:5px;">Renter</th>
            <th style="width:5px;">Phone #</th>
            <th style="width:20px;">Note</th>
            <th style="width:10px;">Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reportlists as $trip)
        <tr>
            <td>{{ $trip->id }}</td>
            <td>{{ $trip->amount }}</td>
            <td>{{ $trip->renter_first_name }}{{ $trip->renter_last_name }}</td>
            <td>{{ $trip->renter_contact_number }}</td>
            <td>{{ $trip->note }}</td>
            <td>{{ $trip->created }}</td>
        </tr>
        @endforeach
        <tr><td height="6" colspan="10"></td></tr>
    </tbody>
</table>
{{ $reportlists->links() }}
