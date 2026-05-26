<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <div class="row form-horizontal">
        @if (!empty($payments))
            <div class="col-md-12">
                <legend>Payment Reciept</legend>
                <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
                    <thead>
                        <tr>
                            <th align="left">#</th>
                            <th align="left">Amount</th>
                            <th align="left">Type</th>
                            <th align="left"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $i = 1;
                        @endphp
                        @foreach ($payments as $payment)
                            <tr>
                                <td>{{ $i++ }}</td>
                                <td>{{ $payment->amount ?? '' }}</td>
                                <td>{{ $paymentTypeValue[$payment->type] ?? '' }}</td>
                                <td>
                                    <a href="javascript:;"
                                        onclick="return getPaymentReceipt('{{ base64_encode($payment->id) }}')">
                                        <i class="icon-image2 icon-2x"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="form-group">
                Sorry, You are not authorize user.
            </div>
        @endif
    </div>
</div>