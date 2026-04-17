{{-- Cake `Elements/popularmarkets/admin_index.ctp` + paging (full navigation; AJAX returns this fragment for `#listing`). --}}
<form method="get" action="{{ $basePath }}/index" name="frm1" id="frm1">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive vehiclelist">
        <tr>
            <th style="width:36px;">
                <input type="checkbox" id="selectAllChildCheckboxs" value="1">
            </th>
            <th valign="top" width="10%">#</th>
            <th valign="top">Name</th>
            <th valign="top">Latitude</th>
            <th valign="top">Longitude</th>
            <th valign="top">Status</th>
            <th valign="top" width="8%">Actions</th>
        </tr>
        @forelse(($popularMarkets ?? collect()) as $row)
            <tr>
                <td>
                    <input type="checkbox" name="select[{{ $row->id }}]" value="{{ $row->id }}" class="select1" style="border:0">
                </td>
                <td valign="top" width="10%">{{ $row->id }}</td>
                <td valign="top">{{ $row->name }}</td>
                <td valign="top">{{ $row->lat }}</td>
                <td valign="top">{{ $row->lng }}</td>
                <td align="center" valign="bottom">
                    @if((int)($row->status ?? 0) === 1)
                        <a href="{{ $basePath }}/status/{{ base64_encode((string)$row->id) }}/0" onclick="return confirm('Are you sure to update this record?')">
                            <img src="{{ legacy_asset('img/green2.jpg') }}" alt="Status" title="Status">
                        </a>
                    @else
                        <a href="{{ $basePath }}/status/{{ base64_encode((string)$row->id) }}/1" onclick="return confirm('Are you sure to update this record?')">
                            <img src="{{ legacy_asset('img/red3.jpg') }}" alt="Status" title="Status">
                        </a>
                    @endif
                </td>
                <td class="action">
                    <a href="{{ $basePath }}/add/{{ base64_encode((string)$row->id) }}"><i class="glyphicon glyphicon-edit"></i></a>
                    &nbsp;
                    <a href="{{ $basePath }}/delete/{{ base64_encode((string)$row->id) }}"><i class="glyphicon glyphicon-trash"></i></a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="padding:12px;">
                    @if($popularMarkets === null)
                        Popular markets table is not available.
                    @else
                        No records found.
                    @endif
                </td>
            </tr>
        @endforelse
        <tr><td colspan="7" style="height:6px;"></td></tr>
    </table>
</form>

@if($popularMarkets && $popularMarkets->total() > 0)
    <section class="pagging" style="margin-top:12px; overflow:hidden;">
        <div style="width:40%; float:left;">
            <form name="frmRecordsPages" action="{{ $basePath }}/index" method="get">
                <label class="text-semibold">Show</label>
                <select name="Record[limit]" class="textbox pagingcls form-control" style="display:inline-block; width:auto; min-width:70px;" onchange="this.form.submit()">
                    @foreach ([25,50,100,200] as $opt)
                        <option value="{{ $opt }}" @selected((int)($limit ?? 25) === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
                <span>&nbsp;Records per page</span>
            </form>
        </div>
        <div class="pull-right" style="margin-top:4px;">
            {{ $popularMarkets->links() }}
        </div>
    </section>
@endif
