@if (!empty($prepaidPlans) && count($prepaidPlans) > 0)
    <div class="panel panel-flat">
        <div class="panel-body">
            <div class="row">
                <div class="col-md-9">
                    <legend class="text-bold">Initial Fee Payment Plans</legend>
                </div>
                @if ($chargeButton)
                    <div class="col-md-3">
                        <span class="pull-right">
                            <button type="button" class="btn btn-primary"
                                onclick="ChargeAllInitialFeePlanPayments('{{ $lease_id }}')" title="Charge All Pending Paymemt">
                                Charge Selected Pendings
                            </button>
                        </span>
                    </div>
                @endif
            </div>
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                <thead>
                    <tr>
                        <td>Amount</td>
                        <td>Charge On</td>
                        <td>Status</td>
                        <td>Error</td>
                        <td>Action</td>
                        <td></td>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($prepaidPlans as $prepaidPlan)
                        @php
                            $amount = data_get($prepaidPlan, 'amount', data_get($prepaidPlan, 'PrepaidPlan.amount', 0));
                            $currentTax = data_get($tax, null, 0);
                            $calculatedAmount = $amount + ($amount * $currentTax / 100);
                            $status = data_get($prepaidPlan, 'status', data_get($prepaidPlan, 'PrepaidPlan.status'));
                            $planId = data_get($prepaidPlan, 'id', data_get($prepaidPlan, 'PrepaidPlan.id'));
                        @endphp
                        <tr>
                            <td>
                                {{ sprintf('%0.2f', $calculatedAmount) }}
                            </td>
                            <td>
                                {{ data_get($prepaidPlan, 'charged_on', data_get($prepaidPlan, 'PrepaidPlan.charged_on', '')) }}
                            </td>
                            <td>
                                @if ($status == 0)
                                    Inactive
                                @elseif ($status == 1)
                                    Active
                                @elseif ($status == 2)
                                    Failed
                                @elseif ($status == 3)
                                    Completed
                                @endif
                            </td>

                            <td>
                                {{ data_get($prepaidPlan, 'last_attempt_error', data_get($prepaidPlan, 'PrepaidPlan.last_attempt_error', '')) }}
                            </td>
                            <td>
                                @if ($status == 0)
                                    &nbsp;&nbsp;
                                    <button type="button" class="btn btn-primary"
                                        onclick="changeInitialFeePaymentPlanStatus({{ $planId }}, 1, '{{ $lease_id }}')"
                                        title="Activate Plan">
                                        <i class="glyphicon glyphicon-ok-circle"></i>
                                    </button>
                                @endif

                                @if ($status == 1)
                                    &nbsp;&nbsp;
                                    <button type="button" class="btn text-danger"
                                        onclick="changeInitialFeePaymentPlanStatus({{ $planId }}, 0, '{{ $lease_id }}')"
                                        title="Make Plan Inactive">
                                        <i class="icon-cancel-square"></i>
                                    </button>
                                @endif

                                @if ($status == 1 || $status == 2)
                                    &nbsp;&nbsp;
                                    <button type="button" class="btn btn-primary"
                                        onclick="retryInitialFeePlanPayment({{ $planId }}, '{{ $lease_id }}')"
                                        title="Paymemt Charge">
                                        <i class="icon-spinner9"></i>
                                    </button>
                                @endif
                            </td>
                            <td>
                                @if ($status != 3)
                                    <input type="checkbox" name="PrepaidPlan[{{ $planId }}]" class="PrepaidPlans"
                                        value="{{ $planId }}" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif