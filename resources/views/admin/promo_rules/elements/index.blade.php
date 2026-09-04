@php
    $limit ??= 50;
    $PromotionRules ??= collect();
@endphp

@include('partials.dispacher.paging_box', ['paginator' => $PromotionRules, 'limit' => $limit, 'position' => 'top'])

<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
    <tr>
        <th>#</th>
        <th>Status</th>
        <th>Coupon Code</th>
        <th>Title</th>
        <th>Discount Type</th>
        <th>Discount</th>
        <th>Created</th>
        <th class="action" width="15%">Action</th>
    </tr>
    @foreach($PromotionRules as $rule)
        <tr>
            <td>
                {{ data_get($rule, 'id', '') }}
            </td>
            <td>
                @if(data_get($rule, 'status'))
                    @php
                        $img = '<img src="' . legacy_asset('img/green2.jpg') . '" border="0" alt="Active" />';
                        $showStatus = ' Deactivate';
                        $status = 0;
                    @endphp
                @else
                    @php
                        $img = '<img src="' . legacy_asset('img/red3.jpg') . '" border="0" alt="Inactive" />';
                        $showStatus = ' Activate';
                        $status = 1;
                    @endphp
                @endif
                <span id="active">
                    <a href="{{ url('/admin/promo_rules/changeStatus/' . base64_encode(data_get($rule, 'id', '')) . '/' . $status) }}"
                        title="Click to{{ $showStatus }}" onclick="return confirm('Are you sure to update this record?')">
                        {!! $img !!}
                    </a>
                </span>
            </td>
            <td>
                {{ data_get($rule, 'promo', '')}}
            </td>
            <td>
                {{ data_get($rule, 'title', '') }}
            </td>
            <td>
                {{ ucfirst(data_get($rule, 'type', '')) }}
            </td>
            <td>
                {{ data_get($rule, 'discount', '') }}
            </td>
            <td>
                {{data_get($rule, 'created', '') }}
            </td>
            <td class="action">
                <a href="{{ url('/admin/promo_rules/delete/' . base64_encode(data_get($rule, 'id', ''))) }}" title="Delete"
                    onclick="return confirm('Are you sure you want to delete this promo?');">
                    <i class="icon-trash"></i>
                </a>
                &nbsp;
                <a href="{{ url('/admin/promo_rules/add/' . base64_encode(data_get($rule, 'id', ''))) }}">
                    <i class="icon-pencil"></i>
                </a>
                &nbsp;
                <a href="{{ url('/admin/promo_rules/promousers/' . base64_encode(data_get($rule, 'id', ''))) }}">
                    <i class="icon-user"></i>
                </a>
            </td>
        </tr>
    @endforeach
    <tr>
        <td height="6" colspan="8"></td>
    </tr>
</table>

@include('partials.dispacher.paging_box', ['paginator' => $PromotionRules, 'limit' => $limit])