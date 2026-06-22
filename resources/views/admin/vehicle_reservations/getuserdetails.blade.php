@php
    $user ??= [];
    $owner ??= '';
    $booking ??= '';
    $paystub ??= false;
    $paybank ??= false;
    $incomeRequired ??= 'N/A';
    $monthlyRent ??= 'N/A';
    $monthlyInsurance ??= 'N/A';
@endphp

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <form action="#" method="POST" id="statusChangeForm" class="form-horizontal">
        @csrf

        <fieldset class="col-md-12">
            <legend class="text-semibold">Renter Details:</legend>

            <div class="row">
                <label class="col-lg-4 control-label">Name :</label>
                <div class="col-lg-8 control-label">
                    {{ data_get($user, 'first_name', '') . ' ' . data_get($user, 'last_name', '') }}
                </div>
            </div>

            <div class="row">
                <label class="col-lg-4 control-label">Email :</label>
                <div class="col-lg-8 control-label">
                    <a href="mailto:{{ data_get($user, 'email', '') }}">{{ data_get($user, 'email', '') }}</a>
                </div>
            </div>

            <div class="row">
                <label class="col-lg-4 control-label">Phone :</label>
                <div class="col-lg-8 control-label">
                    <a
                        href="tel:{{ data_get($user, 'contact_number', '') }}">{{ data_get($user, 'contact_number', '') }}</a>
                </div>
            </div>

            <div class="row">
                <label class="col-lg-4 control-label">Income Stated :</label>
                <div class="col-lg-8 control-label">
                    <a href="javascript:void(0)"  id="statedIncome" data-title="Edit" data-pk="{{ data_get($user, 'id', '') }}"
                        data-url="{{ url('admin/vehicle_reservations/provenincome') }}">
                        {{ data_get($user, 'income.income', 0) }}
                    </a>
                </div>
            </div>

            <div class="row">
                <label class="col-lg-4 control-label">Income Proven :</label>
                <div class="col-lg-8 control-label">
                    <a href="javascript:void(0)"  id="provenIncome" data-title="Edit" data-pk="{{ data_get($user, 'id', '') }}"
                        data-url="{{ url('admin/vehicle_reservations/provenincome') }}">
                        {{ data_get($user, 'income.provenincome', 0) }}
                    </a>
                </div>
            </div>

            <div class="row">
                <label class="col-lg-4 control-label">Income Required :</label>
                <div class="col-lg-2 control-label">
                    <a href="javascript:void(0)" >{{ $incomeRequired }}</a>
                </div>
                <div class="col-lg-6 control-label">
                    (<strong>Monthly Rent: </strong>{{ $monthlyRent }},<br><strong>Monthly Insurance:
                    </strong>{{ $monthlyInsurance }})
                </div>
            </div>

            @if(!empty($plaidObj) && !empty($plaidObj['income_verification_id']))
                <div class="row">
                    <label class="col-lg-4 control-label">Income Doc :</label>
                    <div class="col-lg-8 control-label">
                        <a href="{{ url('admin/plaid_users/downloadpaystub/' . $plaidObj['income_verification_id']) }}"
                            title="Download Pay Stub Doc">
                            <i class="icon-file-download2"></i>
                        </a>
                    </div>
                </div>
            @endif

            <div class="row">
                <label class="col-lg-7 control-label">Driving License</label>
                <div class="col-lg-5 control-label">
                    @if(!empty(data_get($user, 'license_doc_1', '')))
                        <a href="{{ legacy_asset('files/userdocs/' . data_get($user, 'license_doc_1', '')) }}"
                            class="fancybox" title="Driver License">
                            <i class="icon-magazine"></i>
                        </a>
                    @endif
                    @if(!empty(data_get($user, 'license_doc_2', '')))
                        <a href="{{ legacy_asset('files/userdocs/' . data_get($user, 'license_doc_2', '')) }}"
                            class="fancybox" title="Driver License">
                            <i class="icon-magazine"></i>
                        </a>
                    @endif
                </div>
            </div>

            <div class="row">
                <label class="col-lg-7 control-label">Proof of Residency</label>
                <div class="col-lg-5 control-label">
                    @if(
                            !empty(data_get($user, 'address_doc', ''))
                            && is_array(json_decode(data_get($user, 'address_doc', ''), true))
                        )
                        @foreach(json_decode(data_get($user, 'address_doc', ''), true) as $address)
                            <a href="{{ legacy_asset('files/userdocs/' . $address) }}" class="fancybox" title="Address Proof">
                                <i class="icon-magazine"></i>
                            </a>
                        @endforeach
                    @endif

                    <a href="javascript:void(0)"  title="Upload Address Proof"
                        onclick="uploadAddressProof('{{ data_get($user, 'id', '') }}')">
                        <i class="icon-upload"></i>
                    </a>
                </div>
            </div>

            <div class="row">
                <label class="col-lg-4 control-label">Address:</label>
                <div class="col-lg-8 control-label">
                    {{ data_get($user, 'address', '') }} {{ data_get($user, 'city', '') }},
                    {{data_get($user, 'state', '') }} {{ data_get($user, 'zip', '') }}
                    <em>DOB: {{ data_get($user, 'dob', '') }}</em>
                </div>
            </div>

            <div class="row">
                <label class="col-lg-4 control-label">Address as per License</label>
                <div class="col-lg-8 control-label">
                    @if(data_get($user, 'userLicenseDetail'))
                        {{ data_get($user, 'userLicenseDetail.givenName', '') }}
                        {{data_get($user, 'userLicenseDetail.lastName', '') }},
                        {{ data_get($user, 'userLicenseDetail.addressStreet', '') }}
                        {{ data_get($user, 'userLicenseDetail.addressCity', '') }},
                        {{ data_get($user, 'userLicenseDetail.addressState', '') }}
                        {{ data_get($user, 'userLicenseDetail.addressPostalCode', '') }}
                        <em>DOB: {{ data_get($user, 'userLicenseDetail.dateOfBirth', '') }}</em>
                    @endif
                </div>
            </div>

            @if(data_get($user, 'checkr_status') === 0)
                <div class="row">
                    <label class="col-lg-7 control-label">Initiate MVR Report</label>
                    <div class="col-lg-5 control-label">
                        <a href="javascript:void(0)"  title="Check MVR report"
                            onclick="getCheckrReport('{{ data_get($user, 'id', '') }}', {{ $owner }})">
                            <i class="icon-unfold"></i>
                        </a>
                    </div>
                </div>
            @endif

            @if(
                    !data_get($user, 'report.checkr_reportid')
                    || \Carbon\Carbon::parse(data_get($user, 'report.created_at'))->lt(now()->subDays(30))
                )
                <div class="row">
                    <label class="col-lg-7 control-label">Re-Create MVR Report</label>
                    <div class="col-lg-5 control-label">
                        <a href="javascript:void(0)"  title="Request Report Again"
                            onclick="reGenerateReport('{{ base64_encode(data_get($user, 'id', '')) }}', '{{ base64_encode($owner) }}', '{{ !empty($booking) ? base64_encode($booking) : '' }}')">
                            <i class="icon icon-spinner11"></i>
                        </a>
                    </div>
                </div>
            @endif

            @if(
                    filled(data_get($user, 'report.checkr_id'))
                    && blank(data_get($user, 'report.checkr_reportid'))
                    && blank(data_get($user, 'report.motor_vehicle_report_id'))
                )
                <div class="row">
                    <label class="col-lg-7 control-label">Initiate MVR Report</label>
                    <div class="col-lg-5 control-label">
                        <a href="javascript:void(0)"  title="Check MVR report"
                            onclick="getCheckrReport('{{ data_get($user, 'id', '') }}', {{ $owner }})">
                            <i class="icon-unfold"></i>
                        </a>
                    </div>
                </div>
            @endif

            @if(
                    filled(data_get($user, 'report.checkr_reportid'))
                    && filled(data_get($user, 'report.motor_vehicle_report_id'))
                )
                <div class="row">
                    <label class="col-lg-7 control-label">Get MVR Vehicle Report</label>
                    <div class="col-lg-5 control-label">
                        <a href="javascript:void(0)"  title="Vehicle Report"
                            onclick="getVehicleReport('{{ data_get($user, 'report.motor_vehicle_report_id') }}', 'statementModal')">
                            <i class="icon icon-magazine"></i>
                        </a>
                    </div>
                </div>
            @endif
        </fieldset>

        <fieldset class="col-md-6">
            <legend class="text-semibold">Income Doc:</legend>
            <div class="row">
                <label class="col-lg-7 control-label">Address Doc</label>
                <div class="col-lg-5 control-label">
                    @if(filled(data_get($user, 'income.utility_bill')))
                        <a href="{{ legacy_asset('files/userdocs/' . data_get($user, 'income.utility_bill')) }}"
                            class="fancybox" title="Address Doc">
                            <i class="icon-magazine"></i>
                        </a>
                    @endif
                    @if(filled(data_get($user, 'income.utility_bill_2')))
                        <a href="{{ legacy_asset('files/userdocs/' . data_get($user, 'income.utility_bill_2')) }}"
                            class="fancybox" title="Address Doc">
                            <i class="icon-magazine"></i>
                        </a>
                    @endif
                </div>
            </div>

            <div class="row">
                <label class="col-lg-7 control-label">Payment Source</label>
                <div class="col-lg-5 control-label">
                    @if(filled(data_get($user, 'measureOne.id')))
                        <a href="javascript:void(0)"  title="Check Income"
                            onclick="return checkMeasureOneIncome('{{ base64_encode(data_get($user, 'id', '')) }}');">
                            <i class="icon-magazine"></i>
                        </a>
                    @endif
                </div>
            </div>

            <div class="row">
                <label class="col-lg-7 control-label">Bank Statement</label>
                <div class="col-lg-5 control-label">
                    @if(isset($paybank) && $paybank)
                        <a href="javascript:void(0)"  title="Check Plaid Income"
                            onclick="return pullPlaidBank('{{ base64_encode(data_get($user, 'id', '')) }}', 'plaidModal');">
                            <i class="icon-magazine"></i>
                        </a>
                    @else
                        N/A
                    @endif
                </div>
            </div>

            <div class="row">
                <label class="col-lg-7 control-label">Paystub</label>
                <div class="col-lg-5 control-label">
                    @if(isset($paystub) && $paystub)
                        <a href="javascript:void(0)"  title="Check Plaid Uploaded Paystubs"
                            onclick="return pullPlaidPaystub('{{ base64_encode(data_get($user, 'id', '')) }}', 'plaidModal');">
                            <i class="icon-magazine"></i>
                        </a>
                    @else
                        N/A
                    @endif
                </div>
            </div>
        </fieldset>

        <fieldset class="col-md-6">
            <legend class="text-semibold">Credit Files:</legend>
            @if(filled(data_get($user, 'creditScore')))
                @php
                    $data = filled(data_get($user, 'creditScore.data'))
                        ? json_decode(data_get($user, 'creditScore.data'), true)
                        : []; 
                @endphp
                <div class="row">
                    <label class="col-lg-2 control-label">Bureau</label>
                    <div class="col-lg-2 control-label">Score</div>
                    <div class="col-lg-4 control-label">Repossession</div>
                    <div class="col-lg-2 control-label">Log</div>
                </div>
                @foreach ($data as $key => $dat)
                    <div class="row">
                        <label class="col-lg-2 control-label">{{ $key }}</label>
                        <div class="col-lg-2 control-label">{{ $dat['Credit Score'] ?? '' }}</div>
                        <div class="col-lg-4 control-label">{{ $dat['Repossession'] ?? '' }}</div>
                        <div class="col-lg-2 control-label">
                            <a href="{{ url('admin/vehicle_reservations/renderlog/' . data_get($user, 'creditScore.user_id', '') . '_' . $key . '.json') }}"
                                title="See Log File" target="_blank">
                                View
                            </a>
                        </div>
                    </div>
                @endforeach
            @endif
        </fieldset>
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-primary mt-10" data-dismiss="modal">Close</button>
</div>