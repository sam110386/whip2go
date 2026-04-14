{{ $records->links() }}
<!-- Simple list -->
<div class="panel-flat">
    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table  table-responsive">
        <thead>
            <tr>
                <th style="width:5px;">
                    #
                </th>
                <th style="width:5px;">
                    Logo
                </th>
                <th style="width:10px;">
                    Name
                </th>
                <th style="width:5px;">
                    Address
                </th>
                <th style="width:5px;">
                    City
                </th>
                <th style="width:5px;">
                    State
                </th>
                <th style="width:5px;">
                    Country
                </th>
                <th style="width:5px;">
                    Status
                </th>
               
                <th style="width:10px;">
                    Action
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
                <tr>
                    <td>
                        {{ $record->id }}
                    </td>
                    <td>
                        @if(!empty($record->logo))
                            <img src="{{ config('app.url') }}/img/insurance_providers/{{ $record->logo }}" width="80" height="80"/>
                        @endif
                    </td>
                    <td>
                        {{ $record->name }}
                    </td>
                    <td>
                        {{ $record->address }}
                    </td>
                    <td>
                        {{ $record->city }}
                    </td>
                    <td>
                        {{ $record->state }}
                    </td>
                    <td>
                        {{ $record->country }}
                    </td>
                    <td>
                        @if ($record->status == 1)
                            <a href="{{ url('/admin/insuprovider/providers/status/' . base64_encode($record->id) . '/0') }}" onclick="return confirm('Are you sure to update this record?')"><img src="/img/green2.jpg" alt="Status" title="Status"></a>
                        @else
                            <a href="{{ url('/admin/insuprovider/providers/status/' . base64_encode($record->id) . '/1') }}" onclick="return confirm('Are you sure to update this record?')"><img src="/img/red3.jpg" alt="Status" title="Status"></a>
                        @endif
                    </td>
                    <td>
                        &nbsp;
                        <a href="{{ url('/admin/insuprovider/providers/add/' . base64_encode($record->id)) }}"><i class="glyphicon glyphicon-edit"></i></a>
                        &nbsp;
                        <a href="{{ url('/admin/insuprovider/providers/delete/' . base64_encode($record->id)) }}"><i class="glyphicon glyphicon-trash"></i></a>
                    </td>
                </tr>
            @endforeach
            <tr>
                <td heigth="6" colspan="16"></td>
            </tr>
        </tbody>
    </table>
</div>
<!-- /simple list -->
{{ $records->links() }}
