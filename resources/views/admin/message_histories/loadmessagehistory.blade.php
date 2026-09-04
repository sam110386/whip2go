@php
    $phone = optional($csTwilioOrder?->csTwilioLogs?->first())->renter_phone ?? '';
    $csTwilioOrder ??= collect();
    $csTwilioLogs = data_get($csTwilioOrder, 'csTwilioLogs', collect());
@endphp

<div class="panel">
    <section class="right_content">
        <section class="reportListingHeading"
            style="margin-bottom: 7px; float: left; width: 100%; padding: 13px 23px 0;">
            <strong>{{ $phone }}</strong>
            <a href="#" class="btn btn-primary" onclick="loadnewmessgae('{{ $orderId }}')" style="float:right;">
                Send New Message
            </a>
        </section>

        <div style="width:100%; overflow: visible;">
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Type</th>
                        <th class="text-center">Text</th>
                        <th class="text-center">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($csTwilioLogs->isEmpty())
                        <tr id="set_hide">
                            <td colspan="4" class="text-center font-weight-bold">No Record Available!</td>
                        </tr>
                    @else
                        @foreach ($csTwilioLogs as $log)
                            <tr>
                                <td class="text-center">
                                    {{ $loop->iteration }}
                                </td>
                                <td class="text-center">
                                    {{ $log->type == 1 ? 'Sent' : 'Received' }}
                                </td>
                                <td class="text-center">
                                    {{ $log->msg }}
                                </td>
                                <td class="text-center">
                                    {{ optional($log->created_at)->format('Y-m-d h:i A') ?? $log->created }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </section>
</div>