@extends('admin.layouts.app')

@php
    $leads ??= [];
    $limit ??= 50;
    $title ??= 'My Leads';
    $keyword ??= '';
    $fieldname ??= '';
    $date_from ??= '';
    $date_to ??= '';
    $status_type ??= '';
    $type ??= '';
    $status_opt = [
        '0' => 'Pending',
        '2' => 'Canceled',
        '1' => 'Approved'
    ];
    $columns = [
        ['title' => '#', 'field' => 'id', 'style' => 'width:5px;'],
        ['title' => 'Status', 'field' => 'status', 'style' => 'width:10px;'],
        ['title' => 'Phone', 'field' => 'phone', 'style' => 'width:5px;'],
        ['title' => 'Lead Type', 'field' => 'type', 'style' => 'width:5px;'],
        ['title' => 'Name', 'sortable' => false, 'style' => 'width:5px;'],
        ['title' => 'Created', 'field' => 'created', 'style' => 'width:5px;'],
        ['title' => 'By', 'sortable' => false, 'style' => 'width:5px;'],
        ['title' => 'Action', 'sortable' => false, 'style' => 'width:10px;']
    ];
@endphp

@section('title', $title)

@section('content')

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
                                <option value="{{ $k }}" @selected($status_type == $k)>
                                    {{ $v }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="Search[type]" class="form-control">
                            <option value="">Type</option>
                            <option value="dealer" @selected($type == 'dealer')>Dealer</option>
                            <option value="driver" @selected($type == 'driver')>Driver</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control"
                            value="{{ !empty($date_from) ? \Carbon\Carbon::parse($date_from)->format('m/d/Y') : '' }}"
                            placeholder="Date Range From" />
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control"
                            value="{{ !empty($date_to) ? \Carbon\Carbon::parse($date_to)->format('m/d/Y') : '' }}"
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
                            @include('partials.dispacher.sortable_header', compact('columns'))
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leads as $lead)
                            <tr>
                                <td>
                                    {{ data_get($lead, 'id', '') }}
                                </td>
                                <td>
                                    @if(data_get($lead, 'status') == 1)
                                        {{'Approved'}}
                                    @elseif(data_get($lead, 'status') == 2)
                                        {{"Canceled"}}
                                    @else
                                        {{"Pending"}}
                                    @endif
                                </td>
                                <td>
                                    {{ data_get($lead, 'phone', '') }}
                                </td>
                                <td>
                                    {{ data_get($lead, 'type', ) == 1 ? 'Driver' : 'Dealer' }}
                                </td>
                                <td>
                                    {{ data_get($lead, 'type') == 1 ? (data_get($lead, 'first_name') . ' ' . data_get($lead, 'last_name')) : data_get($lead, 'dealer_name', '') }}
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::parse(data_get($lead, 'created'))->format('m/d/Y h:i A') }}
                                </td>
                                <td>
                                    {{ data_get($lead, 'owner_first_name', '') }} {{ data_get($lead, 'owner_last_name', '') }}
                                </td>
                                <td>
                                    @if(data_get($lead, 'status') != 1)
                                        &nbsp;
                                        <a href="{{ url('admin/leads/add/' . base64_encode(data_get($lead, 'id', ''))) }}">
                                            <i class="glyphicon glyphicon-edit"></i>
                                        </a>
                                        &nbsp;
                                        <a href="{{ url('admin/leads/delete/' . base64_encode(data_get($lead, 'id', ''))) }}">
                                            <i class="glyphicon glyphicon-trash"></i>
                                        </a>
                                    @endif
                                    &nbsp;
                                    <a href="javascript:void(0)"
                                        onclick="refreshLead('{{ base64_encode(data_get($lead, 'id', '')) }}')">
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

            @include('partials.dispacher.paging_box', ['paginator' => $leads, 'limit' => $limit])

        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/lead/admin_lead.js') }}"></script>

    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery('#SearchDateFrom').datepicker({
                dateFormat: 'mm/dd/yy'
            });
            jQuery('#SearchDateTo').datepicker({
                dateFormat: 'mm/dd/yy'
            });
        });
    </script>

@endpush