@extends('layouts.main')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage</span> - CC Details</h4>
        </div>
        <div class="heading-elements">
            <a href="{{ url('insurance/payer_tokens/add') }}" class="btn btn-danger btn-lg" style="float:right;">Add New</a>
        </div>
    </div>
</div>
<div class="row ">
    @if(session('flash_message'))
        <div class="alert alert-success">{{ session('flash_message') }}</div>
    @endif
    @if(session('flash_error'))
        <div class="alert alert-danger">{{ session('flash_error') }}</div>
    @endif
</div>
@if(count($rules) > 1)
<div class="panel">
    <div class="panel-body">
        <div class="form-group">
            <div class="col-lg-12">
                <select name="InsurancePayerToken[ruleid]" id="InsurancePayerTokenRuleid" class="form-control">
                    @foreach($rules as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>
@endif
<div class="panel">
    <div id="postsPaging" class="panel-body">
        @if(!empty($InsurancePayerTokens) && count($InsurancePayerTokens) > 0)
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
                <tr>
                    <th valign="top">Card #</th>
                    <th valign="top">Card Type</th>
                    <th valign="top">Created</th>
                    <th valign="top">Default</th>
                    <th valign="top">Actions</th>
                </tr>
                @foreach($InsurancePayerTokens as $Token)
                    <tr>
                        <td valign="top">****{{ $Token['InsurancePayerToken']['card'] }}</td>
                        <td valign="top">{{ $Token['InsurancePayerToken']['card_funding'] }}</td>
                        <td valign="top">{{ \Carbon\Carbon::parse($Token['InsurancePayerToken']['created'])->format('Y-m-d h:i A') }}</td>
                        <td valign="top">{{ $Token['InsurancePayerToken']['is_default'] ? 'Yes' : 'No' }}</td>
                        <td class="action">
                            <a href="{{ url('insurance/payer_tokens/makedefault/' . base64_encode($Token['InsurancePayerToken']['id'])) }}" title="Make Default" onclick="return confirm('Are you sure you want to make this card default?')"><i class=" icon-cc"></i></a>
                            <a href="{{ url('insurance/payer_tokens/delete/' . base64_encode($Token['InsurancePayerToken']['id'])) }}" title="Delete" onclick="return confirm('Are you sure you want to delete this record?')"><i class="icon-trash"></i></a>
                        </td>
                    </tr>
                @endforeach
                <tr><td heigth="6" colspan="5"></td></tr>
            </table>
        @else
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="borderTable">
                <tr>
                    <td colspan="5" align="center">No record found</td>
                </tr>
            </table>
        @endif
    </div>
</div>
<script type="text/javascript">
    $(function(){
        $("#InsurancePayerTokenRuleid").change(function(){
            window.location.href='{{ url("insurance/payer_tokens/index") }}/'+$(this).val();
        });
    })
</script>
@endsection
