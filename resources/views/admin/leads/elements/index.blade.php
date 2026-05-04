@php
    $limit ??= 25;
@endphp
@if(!empty($leads) && $leads->total() > 0)
    <div class="table-responsive">
        <table class="table table-responsive">
            <thead>
                <tr>
                    @include('partials.dispacher.sortable_header', ['columns' => [
                        ['field' => 'id', 'title' => '#', 'style' => 'width:5px;'],
                        ['field' => 'status', 'title' => 'Status', 'style' => 'width:10px;'],
                        ['field' => 'phone', 'title' => 'Phone', 'style' => 'width:5px;'],
                        ['field' => 'type', 'title' => 'Lead Type', 'style' => 'width:5px;'],
                        ['field' => 'first_name', 'title' => 'Name', 'style' => 'width:5px;'],
                        ['field' => 'created', 'title' => 'Created', 'style' => 'width:5px;'],
                        ['field' => 'owner_first_name', 'title' => 'By', 'style' => 'width:5px;'],
                        ['field' => 'actions', 'title' => 'Action', 'sortable' => false, 'style' => 'width:10px;']
                    ]])
                </tr>
            </thead>
            <tbody>
                @foreach($leads as $lead)
                    <tr>
                        <td>{{ $lead->id }}</td>
                        <td>
                            @if($lead->status == 1) Approved
                            @elseif($lead->status == 2) Canceled
                            @else Pending
                            @endif
                        </td>
                        <td>{{ $lead->phone }}</td>
                        <td>{{ $lead->type == 1 ? 'Driver' : 'Dealer' }}</td>
                        <td>{{ $lead->type == 1 ? $lead->first_name . ' ' . $lead->last_name : ($lead->dealer_name ?? '') }}</td>
                        <td>{{ \Carbon\Carbon::parse($lead->created)->format('m/d/Y h:i A') }}</td>
                        <td>{{ $lead->owner_first_name }} {{ $lead->owner_last_name }}</td>
                        <td>
                            @if($lead->status != 1)
                                &nbsp;<a href="{{ url('/admin/leads/add/' . base64_encode($lead->id)) }}"><i class="glyphicon glyphicon-edit"></i></a>
                                &nbsp;<a href="{{ url('/admin/leads/delete/' . base64_encode($lead->id)) }}"><i class="glyphicon glyphicon-trash"></i></a>
                            @endif
                            &nbsp;<a href="javascript:;" onclick="refreshLead('{{ base64_encode($lead->id) }}')"><i class="icon-spinner9"></i></a>
                        </td>
                    </tr>
                @endforeach
                <tr><td height="6" colspan="16"></td></tr>
            </tbody>
        </table>
    </div>
    @include('partials.dispacher.paging_box', ['paginator' => $leads, 'limit' => $limit])
@else
    <div class="table-responsive">
        <table class="table table-bordered">
            <tr>
                <td colspan="8" class="text-center">No record found</td>
            </tr>
        </table>
    </div>
@endif
