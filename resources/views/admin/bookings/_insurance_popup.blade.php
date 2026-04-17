<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <div class="row form-horizontal">
        @if (empty($Lease))
            <div class="form-group">Sorry, You are not authorize user.</div>
        @else
            <div class="{{ !empty($payments) ? 'col-md-6' : 'col-md-12' }}">
                <legend>Documets</legend>
                <div class="form-group">
                    <label class="col-lg-8 control-label">Insurance Card :</label>
                    <div class="col-lg-2">
                        <a href="javascript:;" onclick="return getinsurancedoc('{{ base64_encode((string)($Lease['CsOrder']['id'] ?? 0)) }}')"><i class="icon-magazine icon-2x"></i></a>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-8 control-label">Rental Agreement :</label>
                    <div class="col-lg-4">
                        <a href="javascript:;" onclick="return getagreement('{{ base64_encode((string)($Lease['CsOrder']['id'] ?? 0)) }}')"><i class="icon-file-pdf icon-2x"></i></a>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-8 control-label">Insurance Declaration Doc :</label>
                    <div class="col-lg-4">
                        <a href="javascript:;" onclick="return getDeclarationDoc('{{ base64_encode((string)($Lease['CsOrder']['id'] ?? 0)) }}')"><i class="icon-magazine icon-2x"></i></a>
                    </div>
                </div>
            </div>
            @if (!empty($payments))
                <div class="col-md-6">
                    <legend>Payment Reciept</legend>
                    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                        <thead>
                            <tr>
                                <th align="left">#</th>
                                <th align="left">Amount</th>
                                <th align="left">Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $i => $payment)
                                <tr>
                                    <td class="text-bold">{{ $i + 1 }}</td>
                                    <td>{{ $payment->amount ?? 0 }}</td>
                                    <td>{{ $paymentTypeValue[(int)($payment->type ?? 0)] ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
</div>

