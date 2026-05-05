
<!-- Simple list -->
<div class="table-responsive" style="margin: 10px 0px;">
    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => '#', 'field' => 'id', 'style' => 'width:5px;'],
                    ['title' => 'Logo', 'style' => 'width:5px;', 'sortable' => false],
                    ['title' => 'Name', 'field' => 'name', 'style' => 'width:10px;'],
                    ['title' => 'Address', 'style' => 'width:5px;', 'sortable' => false],
                    ['title' => 'City', 'field' => 'city', 'style' => 'width:5px;'],
                    ['title' => 'State', 'style' => 'width:5px;', 'sortable' => false],
                    ['title' => 'Country', 'style' => 'width:5px;', 'sortable' => false],
                    ['title' => 'Status', 'style' => 'width:5px;', 'sortable' => false],
                    ['title' => 'Action', 'style' => 'width:10px;', 'sortable' => false],
                ]])
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
                            <img src="{{ legacy_asset('img/insurance_providers/' . $record->logo) }}"width="80"
                                height="80" />
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
                            <a href="{{ url('/admin/insurance_providers/status/' . base64_encode($record->id) . '/0') }}"
                                onclick="return confirm('Are you sure to update this record?')"><img src="/img/green2.jpg"
                                    alt="Status" title="Status"></a>
                        @else
                            <a href="{{ url('/admin/insurance_providers/status/' . base64_encode($record->id) . '/1') }}"
                                onclick="return confirm('Are you sure to update this record?')"><img src="/img/red3.jpg"
                                    alt="Status" title="Status"></a>
                        @endif
                    </td>
                    <td>
                        &nbsp;
                        <a href="{{ url('/admin/insurance_providers/add/' . base64_encode($record->id)) }}"><i
                                class="glyphicon glyphicon-edit"></i></a>
                        &nbsp;
                        <a href="{{ url('/admin/insurance_providers/delete/' . base64_encode($record->id)) }}"><i
                                class="glyphicon glyphicon-trash"></i></a>
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
@include('partials.dispacher.paging_box', ['paginator' => $records, 'limit' => $limit])