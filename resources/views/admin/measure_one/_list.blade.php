<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
    <thead>
        <tr>
            <th valign="top">Employer</th>
            <th valign="top">Connected On</th>
            <th valign="top">Status</th>
            <th valign="top">Source</th>
            <th valign="top">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($MeasureOnes as $measureOne)
            <tr>
                <td id="employer_{{ $measureOne->id }}" class="employer">{{ $measureOne->datasource_name }}</td>
                <td>{{ $measureOne->created }}</td>
                <td>
                    @if ($measureOne->status == 0)
                        Not Requested Yet
                    @elseif ($measureOne->status == 1)
                        InProgress
                    @elseif ($measureOne->status == 2)
                        Available
                    @elseif ($measureOne->status == 3)
                        Failed
                    @endif
                </td>
                <td>{{ $measureOne->paystub ? 'Paystub' : 'Bank Connected' }}</td>
                <td>
                    <span class="cursor-pointer" onclick="loadmeasureonestatement('{{ $measureOne->id }}')"><i class="icon-file-download"></i></span>
                </td>
            </tr>
            <tr>
                <td id="empstatement_{{ $measureOne->id }}" colspan="4"></td>
            </tr>
        @endforeach
    </tbody>
</table>
