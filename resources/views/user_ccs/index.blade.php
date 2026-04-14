@extends('layouts.main')

@section('title', $title_for_layout ?? 'Manage User CC Details')

@section('content')
<div class="panel">
    <section class="right_content">
        <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;padding: 13px 23px 0;">
            <h3 style="width: 80%; float: left;">Manage CC Details</h3>
            <a href="/user_ccs/add/{{ $useridB64 }}" class="label label-success" style="float:right;">Add New</a>
        </section>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($UserCcTokens->isEmpty())
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="borderTable">
                <tr>
                    <td colspan="4" align="center">No record found</td>
                </tr>
            </table>
        @else
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                <tr>
                    <th valign="top">Card Type</th>
                    <th valign="top">Card Holder Name</th>
                    <th valign="top">Credit Card #</th>
                    <th valign="top">Created</th>
                    <th valign="top" width="5%">Status</th>
                    <th valign="top" width="15%">Actions</th>
                </tr>
                @foreach($UserCcTokens as $user)
                    @php
                        $idB64 = base64_encode((string) (int) $user->id);
                    @endphp
                    <tr>
                        <td valign="top">{{ $user->card_type }}</td>
                        <td valign="top">{{ $user->card_holder_name }}</td>
                        <td valign="top">****{{ $user->credit_card_number }}</td>
                        <td valign="top">{{ $user->created }}</td>
                        <td align="center" valign="bottom">
                            @if((int) ($user->status ?? 0) === 1)
                                <img src="{{ legacy_asset('img/green2.jpg') }}" alt="Status" title="Status">
                            @else
                                <img src="{{ legacy_asset('img/red3.jpg') }}" alt="Status" title="Status">
                            @endif
                        </td>
                        <td class="action">
                            <a href="/user_ccs/delete/{{ $idB64 }}/{{ $useridB64 }}"
                               title="Delete"
                               onclick="return confirm('Are you sure you want to delete this record?');">
                                <img src="{{ legacy_asset('img/b_drop.png') }}" alt="Delete" style="border:0;">
                            </a>
                        </td>
                    </tr>
                @endforeach
                <tr><td colspan="6" style="height:6px;"></td></tr>
            </table>
        @endif
    </section>
</div>
@endsection
