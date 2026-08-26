@include('partials.dispacher.paging_box', ['paginator' => $history, 'limit' => $limit ?? 10, 'position' => 'top'])

<table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
    <thead>
        <tr>
            <th style="width:5px;">Booking#</th>
            <th style="width:5px;">Note #</th>
            <th style="width:5px;">Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($history as $hist)
            <tr>
                <td>{{ data_get($hist, 'csOrder.increment_id', '') }}</td>
                <td>{{ data_get($hist, 'msg', '') }}</td>
                <td>{{ data_get($hist, 'created', '') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>