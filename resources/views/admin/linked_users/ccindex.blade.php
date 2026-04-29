@extends('admin.layouts.app')

@section('title', 'User Cards')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">User</span> - Cards
            </h4>
        </div>
        <div class="heading-elements">
            <a href="/cloud/linked_users/ccadd/{{ base64_encode((string)$userId) }}" class="btn btn-success">Add Card</a>
            <a href="/cloud/linked_users/index" class="btn btn-default">Return</a>
        </div>
    </div>
</div>

<div class="content">
    @includeif('partials.flash')

    <div class="panel panel-flat">
        <div class="panel-body">
            <table class="table table-responsive">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Card</th>
                        <th>Expiry</th>
                        <th>Default</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cards as $c)
                        <tr>
                            <td>{{ $c->card_name ?? '' }}</td>
                            <td>{{ $c->card_number ?? '' }}</td>
                            <td>{{ $c->expiry_month ?? '' }}/{{ $c->expiry_year ?? '' }}</td>
                            <td>{{ !empty($c->is_default) ? 'Yes' : 'No' }}</td>
                            <td>
                                <a href="/cloud/linked_users/makeccdefault/{{ base64_encode((string)$c->id) }}/{{ base64_encode((string)$userId) }}" class="label label-warning">Make default</a>
                                <a href="/cloud/linked_users/ccdelete/{{ base64_encode((string)$c->id) }}/{{ base64_encode((string)$userId) }}" class="label label-danger" onclick="return confirm('Delete card?')">Delete</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No cards found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
