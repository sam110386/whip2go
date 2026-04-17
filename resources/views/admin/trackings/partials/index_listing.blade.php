{{-- Cake `Elements/trackings/admin_index.ctp` + paging (AJAX target `#listing`). --}}
<div class="table-responsive">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <tr>
            <th valign="top">Vehicle</th>
            <th valign="top">Driver</th>
            <th valign="top">Date</th>
        </tr>
        @forelse(($trackings ?? collect()) as $row)
            <tr>
                <td valign="top">{{ $row->vehicle_name }}</td>
                <td valign="top">{{ trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')) }}</td>
                <td valign="top">
                    @if(!empty($row->created))
                        {{ \Illuminate\Support\Carbon::parse($row->created)->format('m/d/Y h:i A') }}
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" align="center">No record found</td>
            </tr>
        @endforelse
        <tr><td style="height:6px;" colspan="3"></td></tr>
    </table>
</div>

@if($trackings && $trackings->total() > 0)
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
            {{ $trackings->links() }}
        </div>
    </section>
@endif
