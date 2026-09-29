@php
    $notelists ??= collect();
    $limit ??= 50;
@endphp

@include('partials.dispacher.paging_box', ['paginator' => $notelists, 'limit' => $limit, 'position' => 'top'])

<div class="panel panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
        <thead>
            <tr>
                <th align="left">By</th>
                <th align="left">Date</th>
                <th align="left">Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse($notelists as $notelist)
                <tr>
                    <td align="left">
                        {{ data_get($notelist, 'admin.first_name', '') . ' ' . data_get($notelist, 'admin.last_name', '') }}
                    </td>
                    <td align="left">
                        {{ data_get($notelist, 'created', false) ? \Carbon\Carbon::parse(data_get($notelist, 'created'))->format('Y-m-d h:i A') : '' }}
                    </td>
                    <td align="left">
                        {{ data_get($notelist, 'note', '') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" align="left">No notes found for this user.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.dispacher.paging_box', ['paginator' => $notelists, 'limit' => $limit])