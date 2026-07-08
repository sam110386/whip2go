<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <div class="row form-horizontal">
        @if (empty($lease))
            <div class="form-group">Sorry, You are not an authorized user.</div>
        @else
            <div class="{{ !empty($payments) && count($payments) > 0 ? 'col-md-6' : 'col-md-12' }}">
                <legend>Documents</legend>

                <div class="form-group">
                    <label class="col-lg-8 control-label">Insurance Card :</label>
                    <div class="col-lg-2">
                        <a href="javascript:;" onclick="return getinsurancedoc('{{ base64_encode($lease->id) }}')">
                            <i class="icon-magazine icon-2x"></i>
                        </a>
                    </div>
                    @if ($showUpload)
                        <div class="col-lg-2">
                            @if (in_array($insurancePayer, [5, 6, 7]))
                                <a href="javascript:;"
                                    onclick="OpenDiaFleeetBackupQuoteUploadPopUp({{ $vehicleReservationId }}, 'plaidModal')">
                                    <i class="icon-upload icon-2x"></i>
                                </a>
                            @else
                                <a href="javascript:;" onclick="OpenInsurancePayerListPopUp({{ $orderRuleId }}, 'plaidModal')">
                                    <i class="icon-upload icon-2x"></i>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="form-group">
                    <label class="col-lg-8 control-label">Rental Agreement :</label>
                    <div class="col-lg-4">
                        <a href="javascript:;" onclick="return getagreement('{{ base64_encode($lease->id) }}')">
                            <i class="icon-file-pdf icon-2x"></i>
                        </a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-8 control-label">Insurance Declaration Doc :</label>
                    <div class="col-lg-4">
                        <a href="javascript:;" onclick="return getDeclarationDoc('{{ base64_encode($lease->id) }}')">
                            <i class="icon-magazine icon-2x"></i>
                        </a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-8 control-label">Vehicle Registration :</label>
                    <div class="col-lg-4">
                        <a href="javascript:;"
                            onclick="return getVehicleRegistration('{{ base64_encode($lease->vehicle_id) }}')">
                            <i class="icon-image2 icon-2x"></i>
                        </a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-8 control-label">Vehicle Inspection Doc :</label>
                    <div class="col-lg-4">
                        <a href="javascript:;"
                            onclick="return getVehicleInspectionDoc('{{ base64_encode($lease->vehicle_id) }}')">
                            <i class="icon-image2 icon-2x"></i>
                        </a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-8 control-label">Driver License :</label>
                    <div class="col-lg-4">
                        <a href="javascript:;"
                            onclick="return getDriverLicense('{{ base64_encode($lease->renter_id) }}', 1)">
                            <i class="icon-image2 icon-2x"></i>
                        </a>
                        <a href="javascript:;"
                            onclick="return getDriverLicense('{{ base64_encode($lease->renter_id) }}', 2)">
                            <i class="icon-image2 icon-2x"></i>
                        </a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-8 control-label">Docusign Agreement Doc :</label>
                    <div class="col-lg-4 control-label">
                        @if (!empty($insuranceQuoteObj) && isset($insuranceQuoteObj['InsuranceQuote']['id']))
                            <a href="javascript:;"
                                onclick="OpenSignatureDocPopUp('{{ $insuranceQuoteObj['InsuranceQuote']['id'] }}', '{{ $orderRuleId }}', 'plaidModal')">
                                <i class="icon-image2 icon-2x"></i>
                            </a>
                        @else
                            N/A
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-8 control-label">Vehicle Scan By InspektLabs :</label>
                    <div class="col-lg-4 control-label">
                        <a href="javascript:;"
                            onclick="OpenInspektScanPopUp('{{ base64_encode(!empty($lease->parent_id) ? $lease->parent_id : $lease->id) }}', 'plaidModal')">
                            <i class="icon-image2 icon-2x"></i>
                        </a>
                    </div>
                </div>

                @if ($insurancePayer == 5)
                    <div class="form-group">
                        <label class="col-lg-8 control-label">Driver Financed Insurance :</label>
                        <div class="col-lg-4 control-label">
                            @if(isset($insuranceQuoteObj['InsuranceQuote']['id']))
                                <a href="javascript:;"
                                    onclick="OpenDriverFinancedQuotePopUpFromBooking('{{ $insuranceQuoteObj['InsuranceQuote']['id'] }}', 'plaidModal')">
                                    <i class="icon-image2 icon-2x"></i>
                                </a>
                            @else
                                N/A
                            @endif
                        </div>
                    </div>
                @endif

                <div class="form-group">
                    <label class="col-lg-8 control-label">CCM Card :</label>
                    <div class="col-lg-4 control-label">
                        <a href="javascript:;"
                            onclick="getVehicleCCMCard('{{ !empty($lease->parent_id) ? $lease->parent_id : $lease->id }}')">
                            <i class="icon-image2 icon-2x"></i>
                        </a>
                    </div>
                </div>
            </div>

            @if (!empty($payments) && count($payments) > 0)
                <div class="col-md-6">
                    <legend>Payment Receipt</legend>
                    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                        <thead>
                            <tr>
                                <th align="left">#</th>
                                <th align="left">Amount</th>
                                <th align="left">Type</th>
                                <th align="left"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $payment)
                                <tr>
                                    <td class="text-bold">{{ $loop->iteration }}</td>
                                    <td>{{ $payment->amount }}</td>
                                    <td>{{ $paymentTypeValue[$payment->type] ?? 'N/A' }}</td>
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
            @endif
        @endif
    </div>
</div>