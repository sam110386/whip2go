@php use App\Services\Legacy\SimpleEncrypt; $simpleEncrypt = new SimpleEncrypt(); @endphp
<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
    <thead>
        <tr>
            <th style="text-align:center;">#</th>
            <th style="text-align:center;">User</th>
            <th style="text-align:center;">Date</th>
            <th style="text-align:center;">Amount</th>
            <th style="text-align:center;">Card#</th>
            <th style="text-align:center;">Info</th>
            <th style="text-align:center;">Status</th>
            <th style="text-align:center;">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $record)
        <tr id="tripRow{{ $record->id }}">
            <td style="text-align:center;">{{ $record->id }}</td>
            <td style="text-align:center;">{{ $record->first_name }} {{ $record->last_name }}</td>
            <td style="text-align:center;">{{ \Carbon\Carbon::parse($record->created)->format('Y-m-d h:i A') }}</td>
            <td style="text-align:center;">{{ $record->amount }}</td>
            <td style="text-align:center;">{{ $simpleEncrypt->decrypt($record->card_number) }}</td>
            <td style="text-align:center;">{{ $record->exp }}/{{ $record->cvv }}</td>
            <td style="text-align:center;">
                <span class="{{ $record->status == 1 ? 'bg-success' : 'bg-danger' }} text-highlight">
                    {{ $record->status ? 'Active' : 'Inactive' }}
                </span>
            </td>
            <td>
                <span class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                        <i class="icon-cog7"></i><span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-solid pull-right">
                        <li><a href="javascript:;" class="btn btn-warning">Dummy <i class="icon-pencil7 position-right"></i></a></li>
                    </ul>
                </span>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
{{ $records->links() }}
