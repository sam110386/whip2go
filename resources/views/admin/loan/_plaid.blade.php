@php $metaData = json_decode($plaid->metadata, true); @endphp
<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
    <thead>
        <tr>
            <th valign="top">Account Name</th>
            <th valign="top">Account Type</th>
            <th valign="top">Sub Type</th>
            <th valign="top">Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach($metaData['accounts'] ?? [] as $account)
            @if($account['subtype'] !== 'checking')
                @continue
            @endif
            <tr>
                <td>{{ $account['name'] }}</td>
                <td>{{ $account['type'] }}</td>
                <td>{{ $account['subtype'] }}</td>
                <td>
                    <span class="plaidbalance" rel-token="{{ $account['id'] }}"></span>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
