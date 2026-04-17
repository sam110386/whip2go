@extends('admin.layouts.app')

@section('title', 'Vehicle Offers')

@section('content')
    <h1>Vehicle offers</h1>
    <p><a href="{{ $basePath }}/add">Add offer</a></p>
    <form method="get" action="{{ $basePath }}/index" style="margin-bottom:10px;">
        <label>Rows
            <select name="Record[limit]" onchange="this.form.submit()">
                @foreach ([25,50,100,200] as $opt)
                    <option value="{{ $opt }}" @selected((int)($limit ?? 50) === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </label>
    </form>
    <div class="panel panel-flat">
        <div class="table-responsive">
            <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                <thead>
                    <tr>
                        @include('partials.dispacher.sortable_header', ['columns' => [
                            ['field' => 'id', 'title' => 'ID'],
                            ['field' => 'vehicle_name', 'title' => 'Vehicle'],
                            ['field' => 'owner_first_name', 'title' => 'Dealer'],
                            ['field' => 'renter_first_name', 'title' => 'Renter'],
                            ['field' => 'offer_price', 'title' => 'Price'],
                            ['field' => 'status', 'title' => 'Status'],
                            ['field' => 'actions', 'title' => 'Actions', 'sortable' => false]
                        ]])
                    </tr>
                </thead>
                <tbody>
                    @forelse($offers as $o)
                        <tr>
                            <td>{{ $o->id }}</td>
                            <td>{{ $o->vehicle_unique_id }} - {{ $o->vehicle_name }}</td>
                            <td>{{ trim(($o->owner_first_name ?? '') . ' ' . ($o->owner_last_name ?? '')) }}</td>
                            <td>{{ trim(($o->renter_first_name ?? '') . ' ' . ($o->renter_last_name ?? '')) }}</td>
                            <td>{{ number_format((float)($o->offer_price ?? 0), 2) }}</td>
                            <td>{{ $o->status }}</td>
                            <td>
                                <a href="{{ $basePath }}/view/{{ base64_encode((string)$o->id) }}" title="View"><i class="icon-clipboard3"></i></a>
                                <a href="{{ $basePath }}/add/{{ base64_encode((string)$o->id) }}" title="Edit"><i class="icon-pencil"></i></a>
                                <a href="{{ $basePath }}/duplicate/{{ base64_encode((string)$o->id) }}" title="Duplicate"><i class="icon-copy3"></i></a>
                                <a href="{{ $basePath }}/cancel/{{ base64_encode((string)$o->id) }}" title="Cancel"><i class="icon-cross2"></i></a>
                                <a href="{{ $basePath }}/delete/{{ base64_encode((string)$o->id) }}" onclick="return confirm('Delete this offer?')" title="Delete"><i class="icon-trash"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" align="center">No offers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @include('partials.dispacher.paging_box', ['paginator' => $offers, 'limit' => $limit ?? 50])
@endsection

