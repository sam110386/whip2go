@extends('admin.layouts.app')
@section('content')
    @php
        $status_opt = ['complete' => 'Pending', 'cancel' => 'Canceled', 'incomplete' => 'Approved'];
    @endphp
    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content" style="min-height: 200px;"></div>
        </div>
    </div>

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i> 
                    <span class="text-semibold">Manage</span> - Leads
                </h4>
            </div>
            <div class="heading-elements">
                <div class="input-group-btn">
                    <a href="{{ url('/admin/leads/add') }}" class="btn btn-success" style="float:right;">
                        Add New
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form action="{{ url('/admin/leads/index') }}" method="POST" id="frmSearchadmin" class="form-horizontal">
                @csrf
                <div class="row pb-10">
                    <div class="col-md-2">
                        <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}"
                            placeholder="Keyword" />
                    </div>
                    <div class="col-md-2">
                        <select name="Search[status_type]" class="form-control">
                            <option value="">Status</option>
                            @foreach($status_opt as $k => $v)
                                <option value="{{ $k }}" {{ $statusType == $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="Search[type]" class="form-control">
                            <option value="">Type</option>
                            <option value="dealer" {{ $type == 'dealer' ? 'selected' : '' }}>Dealer</option>
                            <option value="driver" {{ $type == 'driver' ? 'selected' : '' }}>Driver</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control"
                            value="{{ !empty($dateFrom) ? \Carbon\Carbon::parse($dateFrom)->format('m/d/Y') : '' }}"
                            placeholder="Date Range From" />
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control"
                            value="{{ !empty($dateTo) ? \Carbon\Carbon::parse($dateTo)->format('m/d/Y') : '' }}"
                            placeholder="Date Range To" />
                    </div>
                    <div class="col-md-2">
                        <input type="submit" value="APPLY" class="btn btn-primary" />
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="panel">
        <div class="panel-body" id="listing">
            <div class="table-responsive">
                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                    <thead>
                        <tr>
                            @include('partials.dispacher.sortable_header', [
                                'columns' => [
                                    ['title' => '#', 'field' => 'id', 'style' => 'width:5px;'],
                                    ['title' => 'Status', 'field' => 'status', 'style' => 'width:10px;'],
                                    ['title' => 'Phone', 'field' => 'phone', 'style' => 'width:5px;'],
                                    ['title' => 'Lead Type', 'field' => 'type', 'style' => 'width:5px;'],
                                    ['title' => 'Name', 'sortable' => false, 'style' => 'width:5px;'],
                                    ['title' => 'Created', 'field' => 'created', 'style' => 'width:5px;'],
                                    ['title' => 'By', 'sortable' => false, 'style' => 'width:5px;'],
                                    ['title' => 'Action', 'sortable' => false, 'style' => 'width:10px;']
                                ]
                            ])
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leads as $lead)
                            <tr>
                                <td> {{ $lead->id }} </td>
                                <td>
                                    @if($lead->status == 1) 
                                        {{'Approved'}}
                                    @elseif($lead->status == 2) 
                                        {{"Canceled"}}
                                    @else 
                                        {{"Pending"}}
                                    @endif
                                </td>
                                <td>{{ $lead->phone }}</td>
                                <td>{{ $lead->type == 1 ? 'Driver' : 'Dealer' }}</td>
                                <td>{{ $lead->type == 1 ? "{$lead->first_name} {$lead->last_name}" : ($lead->dealer_name ?? '') }}</td>
                                <td>{{ \Carbon\Carbon::parse($lead->created)->format('m/d/Y h:i A') }}</td>
                                <td>{{ $lead->owner_first_name }} {{ $lead->owner_last_name }}</td>
                                <td>
                                    @if($lead->status != 1)
                                        &nbsp;
                                        <a href="{{ url('/admin/leads/add/' . base64_encode($lead->id)) }}">
                                            <i class="glyphicon glyphicon-edit"></i>
                                        </a>
                                        &nbsp;
                                        <a href="{{ url('/admin/leads/delete/' . base64_encode($lead->id)) }}">
                                            <i class="glyphicon glyphicon-trash"></i>
                                        </a>
                                    @endif
                                    &nbsp;
                                    <a href="javascript:;" onclick="refreshLead('{{ base64_encode($lead->id) }}')">
                                        <i class="icon-spinner9"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td height="6" colspan="16"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
             @include('partials.dispacher.paging_box', ['paginator' => $leads, 'limit' => $limit ?? 25])
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('Lead/js/admin_lead.js') }}"></script>
    <script type="text/javascript">
        jQuery(document).ready(function() {
            jQuery('#SearchDateFrom').datepicker({ dateFormat: 'mm/dd/yy' });
            jQuery('#SearchDateTo').datepicker({ dateFormat: 'mm/dd/yy' });
        });
    </script>
@endpush