@extends('admin.layouts.app')

@section('title', $title_for_layout ?? 'Manage User CC Details')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">{{ 'Manage' }}</span>
                    {{ 'CC Details' }}
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ url('admin/user_ccs/add', $useridB64) }}" class="btn btn-success">
                    {{ 'Add New' }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>
<div class="panel">
    <div class="panel-body">
        <div id="listing">
        <div class="table-responsive">
            <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                <thead>
                    <tr>
                        @include('partials.dispacher.sortable_header', ['columns' => [
                            ['title' => 'Card Type', 'field' => 'card_type'],
                            ['title' => 'Card Holder Name', 'field' => 'card_holder_name'],
                            ['title' => 'Credit Card #', 'field' => 'credit_card_number'],
                            ['title' => 'Created', 'field' => 'created'],
                            ['title' => 'Status', 'field' => 'status'],
                            ['title' => 'Default', 'sortable' => false],
                            ['title' => 'Actions', 'sortable' => false]
                        ]])
                    </tr>
                </thead>
                <tbody>
                    @forelse($UserCcTokens as $user)
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
                                    <span class="label label-success">Default</span>
                                @else
                                    <a href="/admin/user_ccs/makeccdefault/{{ $idB64 }}/{{ $useridB64 }}"
                                       class="label label-warning"
                                       onclick="return confirm('Are you sure to update this record?')">Make Default</a>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="/admin/user_ccs/delete/{{ $idB64 }}/{{ $useridB64 }}"
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this record?');">
                                    <i class="icon-trash"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" align="center">No record found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>
    </div>
</div>
@endsection
