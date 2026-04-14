<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
    <tr>
        <th>#</th>
        <th>User #</th>
        <th>Name</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Subscribed</th>
        <th class="action" width="15%">Action</th>
    </tr>
    @foreach($PromoTerms as $term)
        <tr>
            <td>{{ $term->id }}</td>
            <td>{{ $term->user_id }}</td>
            <td>{{ $term->first_name }} {{ $term->last_name }}</td>
            <td>{{ $term->contact_number }}</td>
            <td>{{ $term->email }}</td>
            <td>{{ \Carbon\Carbon::parse($term->created)->format('Y-m-d') }}</td>
            <td class="action">
                <a href="javascript:;" title="Delete" onclick="return removePromo('{{ $term->id }}');"><i class="icon-trash"></i></a>
            </td>
        </tr>
    @endforeach
    <tr>
        <td height="6" colspan="7"></td>
    </tr>
</table>
{{ $PromoTerms->links() }}
