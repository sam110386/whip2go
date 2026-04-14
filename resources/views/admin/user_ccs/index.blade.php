@extends('admin.layouts.app')

@section('title', $title_for_layout ?? 'Manage User CC Details')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage</span> CC Details</h4>
        </div>
        <div class="heading-elements">
            <a href="/admin/user_ccs/add/{{ $useridB64 }}" class="btn btn-success">Add New</a>
        </div>
    </div>
</div>
<div class="row">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
</div>
<div class="panel">
    <div class="panel-body">
        <div id="listing">
            @if($UserCcTokens->isEmpty())
                <table width="100%" cellpadding="2" cellspacing="1" border="0" class="borderTable">
                    <tr>
                        <td colspan="4" align="center">No record found</td>
                    </tr>
                </table>
            @else
                <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                    <tr>
                        <th class="text-center">Card Type</th>
                        <th class="text-center">Card Holder Name</th>
                        <th class="text-center">Credit Card #</th>
                        <th class="text-center">Created</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Default</th>
                        <th class="text-center">Actions</th>
                    </tr>
                    @foreach($UserCcTokens as $user)
                        @php
                            $idB64 = base64_encode((string) (int) $user->id);
                        @endphp
                        <tr>
                            <td class="text-center">{{ $user->card_type }}</td>
                            <td class="text-center">{{ $user->card_holder_name }}</td>
                            <td class="text-center">****{{ $user->credit_card_number }}</td>
                            <td class="text-center">{{ $user->created }}</td>
                            <td class="text-center" valign="bottom">
                                @if((int) ($user->status ?? 0) === 1)
                                    <img src="{{ legacy_asset('img/green2.jpg') }}" alt="Status" title="Status">
                                @else
                                    <img src="{{ legacy_asset('img/red3.jpg') }}" alt="Status" title="Status">
                                @endif
                            </td>
                            <td class="text-center" valign="bottom">
                                @if((int) $user->id === (int) ($defaultcctoken ?? 0))
                                    <a href="#" class="label label-success">Default</a>
                                @else
                                    <a href="/admin/user_ccs/makeccdefault/{{ $idB64 }}/{{ $useridB64 }}"
                                       class="label label-warning"
                                       onclick="return confirm('Are you sure to update this record?')">Make Default</a>
                                @endif
                            </td>
                            <td class="text-center action">
                                <a href="/admin/user_ccs/delete/{{ $idB64 }}/{{ $useridB64 }}"
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this record?');">
                                    <img src="{{ legacy_asset('img/b_drop.png') }}" alt="Delete" style="border:0;">
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    <tr><td colspan="7" style="height:6px;"></td></tr>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
