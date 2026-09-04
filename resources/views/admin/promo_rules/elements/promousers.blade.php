@php
    $limit ??= 50;
    $PromoTerms ??= collect();
@endphp

@include('partials.dispacher.paging_box', ['paginator' => $PromoTerms, 'limit' => $limit, 'position' => 'top'])

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
            <td>
                {{ data_get($term, 'id', '') }}
            </td>
            <td>
                {{ data_get($term, 'user_id', '') }}
            </td>
            <td>
                {{ data_get($term, 'user.first_name', '') }} {{data_get($term, 'user.last_name', '')}}
            </td>
            <td>
                {{ data_get($term, 'user.contact_number', '') }}
            </td>
            <td>
                {{ data_get($term, 'user.email', '') }}
            </td>
            <td>
                {{ \Carbon\Carbon::parse(data_get($term, 'created'))->format('Y-m-d') }}
            </td>
            <td class="action">
                <a href="javascript:void(0)" title="Delete"
                    onclick="return removePromo('{{ data_get($term, 'id', '') }}');">
                    <i class="icon-trash"></i>
                </a>
            </td>
        </tr>
    @endforeach
    <tr>
        <td height="6" colspan="7"></td>
    </tr>
</table>

@include('partials.dispacher.paging_box', ['paginator' => $PromoTerms, 'limit' => $limit ?? 50])