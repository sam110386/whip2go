@php
    $metaData = is_array($plaid->metadata) ? $plaid->metadata : json_decode($plaid->metadata, true); 
@endphp

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
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
            @foreach (($metaData['accounts'] ?? []) as $account)
                @continue($account['subtype'] !== 'checking')
                <tr>
                    <td>{{ $account['name'] }}</td>
                    <td>{{ $account['type'] }}</td>
                    <td>{{ $account['subtype'] }}</td>
                    <td>
                        <span class="plaidbalance" rel-token="{{ $account['id'] }}"></span>
                        <a href="javascript:;" title="Bank Statement"
                            onclick="loadbankstatement('{{ $plaid->token }}', '{{ $plaid->user_id }}', '{{ $account['id'] }}')">
                            <i class="icon-magazine"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>