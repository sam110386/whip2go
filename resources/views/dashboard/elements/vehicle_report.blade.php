{{-- Cake `Elements/dashboard/vehicle_report.ctp` (state inspection loop fixed: use StateinspExpVehiles). --}}
<div class=" panel-flat">
    <div class="panel text-center bg-theme">
        <div class="panel-heading">
            <h6 class="panel-title">Vehicle Report</h6>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 panelwraper">
            <div class="panel-heading bg-indigo-400">
                <h6 class="panel-title">Expired Insurance</h6>
                <div class="heading-elements">
                    <ul class="icons-list">
                        <li><a data-action="collapse"></a></li>
                        <li><a data-action="close"></a></li>
                    </ul>
                </div>
            </div>
            <div class="panel panel-body" style="display: none;">
                <div id="sales-heatmap">
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            @if(!empty($InsuranceExpVehiles))
                                @foreach($InsuranceExpVehiles as $key => $date)
                                    <tr>
                                        <td><span class="text-normal">{{ e($key) }}</span></td>
                                        <td>{{ e($date) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr><td colspan="2"><span class="text-normal">No record found</span></td></tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mt-5 panelwraper">
            <div class="panel-heading bg-indigo-400">
                <h6 class="panel-title">Expired Inspection</h6>
                <div class="heading-elements">
                    <ul class="icons-list">
                        <li><a data-action="collapse"></a></li>
                        <li><a data-action="close"></a></li>
                    </ul>
                </div>
            </div>
            <div class="panel panel-body" style="display: none;">
                <div id="sales-heatmap">
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            @if(!empty($InspectionExpVehiles))
                                @foreach($InspectionExpVehiles as $key => $date)
                                    <tr>
                                        <td><span class="text-normal">{{ e($key) }}</span></td>
                                        <td>{{ e($date) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr><td colspan="2"><span class="text-normal">No record found</span></td></tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mt-5 panelwraper">
            <div class="panel-heading bg-indigo-400">
                <h6 class="panel-title">Expired State Inspection</h6>
                <div class="heading-elements">
                    <ul class="icons-list">
                        <li><a data-action="collapse"></a></li>
                        <li><a data-action="close"></a></li>
                    </ul>
                </div>
            </div>
            <div class="panel panel-body" style="display: none;">
                <div id="sales-heatmap">
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            @if(!empty($StateinspExpVehiles))
                                @foreach($StateinspExpVehiles as $key => $date)
                                    <tr>
                                        <td><span class="text-normal">{{ e($key) }}</span></td>
                                        <td>{{ e($date) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr><td colspan="2"><span class="text-normal">No record found</span></td></tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mt-5 panelwraper">
            <div class="panel-heading bg-indigo-400">
                <h6 class="panel-title">Expired Registration</h6>
                <div class="heading-elements">
                    <ul class="icons-list">
                        <li><a data-action="collapse"></a></li>
                        <li><a data-action="close"></a></li>
                    </ul>
                </div>
            </div>
            <div class="panel panel-body" style="display: none;">
                <div id="sales-heatmap">
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            @if(!empty($RegnameexpExpVehiles))
                                @foreach($RegnameexpExpVehiles as $key => $date)
                                    <tr>
                                        <td><span class="text-normal">{{ e($key) }}</span></td>
                                        <td>{{ e($date) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr><td colspan="2"><span class="text-normal">No record found</span></td></tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mt-5 panelwraper">
            <div class="panel-heading bg-indigo-400">
                <h6 class="panel-title">Waiting For Service</h6>
                <div class="heading-elements">
                    <ul class="icons-list">
                        <li><a data-action="collapse"></a></li>
                        <li><a data-action="close"></a></li>
                    </ul>
                </div>
            </div>
            <div class="panel panel-body" style="display: none;">
                <div id="sales-heatmap">
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            @if(!empty($WaitingforServiceVehicles))
                                @foreach($WaitingforServiceVehicles as $key => $date)
                                    <tr>
                                        <td><span class="text-normal">{{ e($key) }}</span></td>
                                        <td>{{ e($date) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr><td colspan="2"><span class="text-normal">No record found</span></td></tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mt-5 panelwraper">
            <div class="panel-heading bg-indigo-400">
                <h6 class="panel-title">In Service</h6>
                <div class="heading-elements">
                    <ul class="icons-list">
                        <li><a data-action="collapse"></a></li>
                        <li><a data-action="close"></a></li>
                    </ul>
                </div>
            </div>
            <div class="panel panel-body" style="display: none;">
                <div id="sales-heatmap">
                    <div class="table-responsive">
                        <table class="table text-nowrap">
                            @if(!empty($InServiceVehicles))
                                @foreach($InServiceVehicles as $key => $date)
                                    <tr>
                                        <td><span class="text-normal">{{ e($key) }}</span></td>
                                        <td>{{ e($date) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr><td colspan="2"><span class="text-normal">No record found</span></td></tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
