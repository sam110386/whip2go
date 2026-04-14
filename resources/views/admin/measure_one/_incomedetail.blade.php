@foreach ($MeasureOneLibObj['income_employment_details'] as $income_employment_details)
    <div class="row">
        <div class="panel panel-flat panel-collapsed">
            <div class="panel-body form-horizontal">
                <fieldset class="col-md-6">
                    <legend class="text-semibold">Details :</legend>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Last Processed :</label>
                        <div class="col-lg-8 control-label">{{ \Carbon\Carbon::createFromTimestamp($income_employment_details['as_of_date'] / 1000)->format('Y-m-d h:i A') }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Yearly Earning :</label>
                        <div class="col-lg-8 control-label">{{ is_array($income_employment_details['yearly_earnings']) ? json_encode($income_employment_details['yearly_earnings']) : $income_employment_details['yearly_earnings'] }}</div>
                    </div>
                    <legend class="text-semibold">Employee Details :</legend>
                    @if (isset($income_employment_details['employee']) && $employee = $income_employment_details['employee'])
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Name :</label>
                            <div class="col-lg-8 control-label">{{ $employee['first_name'] . ' ' . $employee['last_name'] }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Address :</label>
                            <div class="col-lg-8 control-label">
                                <pre class="content-group language-markup p-1"><code class="language-markup">{{ is_array($employee['address']) ? json_encode($employee['address'], JSON_PRETTY_PRINT) : $employee['address'] }}</code></pre>
                            </div>
                        </div>
                        @if (isset($employee['service_details']) && $serviceDetails = $employee['service_details'])
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Employee Id :</label>
                                <div class="col-lg-8 control-label">{{ $serviceDetails['employee_id'] }}</div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Employement Status :</label>
                                <div class="col-lg-8 control-label">{{ $serviceDetails['status'] }}</div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Termination Note :</label>
                                <div class="col-lg-8 control-label">{{ $serviceDetails['termination_reason'] }}</div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Salary :</label>
                                <div class="col-lg-8 control-label">{{ $serviceDetails['salary'] }}</div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Hire Date :</label>
                                <div class="col-lg-8 control-label">{{ !empty($serviceDetails['hire_date']) ? \Carbon\Carbon::createFromTimestamp($serviceDetails['hire_date'] / 1000)->format('Y-m-d h:i A') : '--' }}</div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Termination Date :</label>
                                <div class="col-lg-8 control-label">{{ !empty($serviceDetails['termination_date']) ? \Carbon\Carbon::createFromTimestamp($serviceDetails['termination_date'] / 1000)->format('Y-m-d h:i A') : '--' }}</div>
                            </div>
                        @endif
                    @else
                        N/A
                    @endif

                    @if (isset($income_employment_details['roles']) && $roles = $income_employment_details['roles'])
                        <legend class="text-semibold">Employee Job Title :</legend>
                        @foreach ($roles as $role)
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Position :</label>
                                <div class="col-lg-8 control-label">{{ $role['position']['name'] }} ({{ $role['type'] }})</div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Duration :</label>
                                <div class="col-lg-8 control-label">{{ !empty($role['interval']['start_date']) ? \Carbon\Carbon::createFromTimestamp($role['interval']['start_date'] / 1000)->format('Y-m-d h:i A') : '--' }} to {{ !empty($role['interval']['end_date']) ? \Carbon\Carbon::createFromTimestamp($role['interval']['end_date'] / 1000)->format('Y-m-d h:i A') : '--' }}</div>
                            </div>
                        @endforeach
                    @endif

                    <legend class="text-semibold">Employer Details :</legend>
                    @if (isset($income_employment_details['employer']) && $employer = $income_employment_details['employer'])
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Name :</label>
                            <div class="col-lg-8 control-label">{{ $employer['name'] }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Phone :</label>
                            <div class="col-lg-8 control-label">{{ $employer['phone_number'] }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Address :</label>
                            <div class="col-lg-8 control-label">
                                <pre class="content-group language-markup p-1"><code class="language-markup">{{ is_array($employer['address']) ? json_encode($employer['address'], JSON_PRETTY_PRINT) : $employer['address'] }}</code></pre>
                            </div>
                        </div>
                    @else
                        N/A
                    @endif
                </fieldset>

                <fieldset class="col-md-6">
                    <legend class="text-semibold">Earnings :</legend>
                    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                        <tr>
                            <th width="33%">Type</th>
                            <th width="33%">Basis</th>
                            <th width="33%">Date Range</th>
                        </tr>
                        @foreach ($income_employment_details['earnings'] ?? [] as $earning)
                            <tr>
                                <td>{{ $earning['type'] }}</td>
                                <td>{{ $earning['basis'] }}</td>
                                <td>{{ \Carbon\Carbon::createFromTimestamp((int)($earning['interval']['start_date'] / 1000))->format('Y-m-d h:i A') }} - {{ \Carbon\Carbon::createFromTimestamp((int)($earning['interval']['end_date'] / 1000))->format('Y-m-d h:i A') }}</td>
                            </tr>
                            @foreach ($earning['amounts'] ?? [] as $amount)
                                <tr>
                                    <td></td>
                                    <td>{{ $amount['type'] }}</td>
                                    <td>{{ $amount['currency'] }}{{ $amount['value'] }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </table>

                    <legend class="text-semibold">Other Benifits :</legend>
                    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                        <tr>
                            <th width="33%">Type</th>
                            <th width="33%">Basis</th>
                            <th width="33%">Date Range</th>
                        </tr>
                        @foreach ($income_employment_details['benefits'] ?? [] as $earning)
                            <tr>
                                <td>{{ $earning['type'] }}</td>
                                <td>{{ $earning['basis'] }}</td>
                                <td>{{ \Carbon\Carbon::createFromTimestamp((int)($earning['interval']['start_date'] / 1000))->format('Y-m-d h:i A') }} - {{ \Carbon\Carbon::createFromTimestamp((int)($earning['interval']['end_date'] / 1000))->format('Y-m-d h:i A') }}</td>
                            </tr>
                            @foreach ($earning['amounts'] ?? [] as $amount)
                                <tr>
                                    <td></td>
                                    <td>{{ $amount['type'] }}</td>
                                    <td>{{ $amount['currency'] }}{{ $amount['value'] }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </table>

                    <legend class="text-semibold">Deductions :</legend>
                    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                        <tr>
                            <th width="33%">Type</th>
                            <th width="33%">Basis</th>
                            <th width="33%">Date Range</th>
                        </tr>
                        @foreach ($income_employment_details['deductions'] ?? [] as $earning)
                            <tr>
                                <td>{{ $earning['type'] }}</td>
                                <td>{{ $earning['basis'] }}</td>
                                <td>{{ \Carbon\Carbon::createFromTimestamp((int)($earning['interval']['start_date'] / 1000))->format('Y-m-d h:i A') }} - {{ \Carbon\Carbon::createFromTimestamp((int)($earning['interval']['end_date'] / 1000))->format('Y-m-d h:i A') }}</td>
                            </tr>
                            @foreach ($earning['amounts'] ?? [] as $amount)
                                <tr>
                                    <td></td>
                                    <td>{{ $amount['type'] }}</td>
                                    <td>{{ $amount['currency'] }}{{ $amount['value'] }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </table>
                </fieldset>
            </div>
        </div>
    </div>
@endforeach
