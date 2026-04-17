@extends('layouts.admin')
@section('content')
@php
$status_opt = ['complete' => 'Pending', 'cancel' => 'Canceled', 'incomplete' => 'Approved'];
@endphp
<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery('#SearchDateFrom').datepicker({ dateFormat: 'mm/dd/yy' });
        jQuery('#SearchDateTo').datepicker({ dateFormat: 'mm/dd/yy' });
    });
</script>

<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content" style="min-height: 200px;"></div>
    </div>
</div>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage</span> - Leads</h4>
        </div>
        <div class="heading-elements">
            <div class="input-group-btn">
                <a href="{{ url('/cloud/lead/leads/add') }}" class="btn btn-success" style="float:right;">Add New</a>
            </div>
        </div>
    </div>
</div>
<div class="row">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
</div>
<div class="panel">
    <div class="panel-body">
        <form action="{{ url('/cloud/lead/leads/index') }}" method="POST" id="frmSearchadmin" class="form-horizontal">
            @csrf
            <div class="row pb-10">
                <div class="col-md-2">
                    <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" placeholder="Keyword" />
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
                    <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control" value="{{ !empty($dateFrom) ? \Carbon\Carbon::parse($dateFrom)->format('m/d/Y') : '' }}" placeholder="Date Range From" />
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control" value="{{ !empty($dateTo) ? \Carbon\Carbon::parse($dateTo)->format('m/d/Y') : '' }}" placeholder="Date Range To" />
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
        <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
            <thead>
                <tr>
                    <th style="width:5px;">#</th>
                    <th style="width:10px;">Status</th>
                    <th style="width:5px;">Phone</th>
                    <th style="width:5px;">Lead Type</th>
                    <th style="width:5px;">Name</th>
                    <th style="width:5px;">Created</th>
                    <th style="width:5px;">By</th>
                    <th style="width:10px;">Action</th>
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
                            &nbsp;<a href="{{ url('/cloud/lead/leads/add/' . base64_encode($lead->id)) }}"><i class="glyphicon glyphicon-edit"></i></a>
                            &nbsp;<a href="{{ url('/cloud/lead/leads/delete/' . base64_encode($lead->id)) }}"><i class="glyphicon glyphicon-trash"></i></a>
                        @endif
                        &nbsp;<a href="javascript:;" onclick="refreshLead('{{ base64_encode($lead->id) }}')"><i class="icon-spinner9"></i></a>
                    </td>
                </tr>
                @endforeach
                <tr><td height="6" colspan="16"></td></tr>
            </tbody>
        </table>
        {{ $leads->links() }}
    </div>
</div>
<script src="{{ asset('Lead/js/cloud_lead.js') }}"></script>
@endsection
