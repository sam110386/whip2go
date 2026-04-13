<div class="table-responsive" style="margin: 10px 0px;">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                @include('legacy.elements.dispacher.sortable_header', ['columns' => [
                            ['field' => 'id', 'title' => '#'],
                            ['field' => 'first_name', 'title' => 'First Name'],
                            ['field' => 'last_name', 'title' => 'Last Name'],
                            ['field' => 'email', 'title' => 'Email', 'style' => 'width: 30px;'],
                            ['field' => 'contact_number', 'title' => 'Contact#'],
                            ['field' => 'created', 'title' => 'Created'],
                            ['field' => 'status', 'title' => 'Status'],
                            ['field' => 'is_verified', 'title' => 'Verified'],
                            ['field' => 'is_renter', 'title' => 'Renter'],
                            ['field' => 'is_driver', 'title' => 'Driver'],
                            ['field' => 'is_dealer', 'title' => 'Dealer'],
                            ['field' => 'checkr_status', 'title' => 'Checkr Status'],
                            ['field' => 'trash', 'title' => 'Deleted'],
                            ['field' => 'actions', 'title' => 'Actions', 'sortable' => false]
                        ]])
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td valign="top">{{ $user->id }}</td>
                    <td valign="top">{{ $user->first_name }}</td>
                    <td valign="top">{{ $user->last_name }}</td>
                    <td valign="top">{{ $user->email }}</td>
                    <td valign="top">{{ $user->contact_number }}</td>
                    <td valign="top">{{ $user->created ? \Carbon\Carbon::parse($user->created)->format('m/d/Y h:i A') : '' }}</td>

                    <td align="center" valign="bottom">
                        <a href="{{ url('admin/users/status/' . base64_encode($user->id) . '/' . ($user->status == 1 ? 0 : 1)) }}"
                            onclick="return confirm('Are you sure to update this User?')">
                            <img src="{{ asset($user->status == 1 ? 'img/green2.jpg' : 'img/red3.jpg') }}"
                                title="Status" alt="Status">
                        </a>
                    </td>

                    <td align="center" valign="bottom">
                        @if (!$user->is_admin)
                            @if ($user->is_verified == 1)
                                <img src="{{ asset('img/green2.jpg') }}" alt="Status">
                            @else
                                <a href="{{ url('admin/users/verify/' . base64_encode($user->id)) }}"
                                    onclick="return confirm('Are you sure?')">
                                    <img src="{{ asset('img/red3.jpg') }}" alt="Verify">
                                </a>
                            @endif
                        @endif
                    </td>

                    <td align="center" valign="bottom">
                        @if (!$user->is_admin)
                            <img src="{{ asset($user->is_renter == 1 ? 'img/green2.jpg' : 'img/red3.jpg') }}"
                                alt="Renter">
                        @endif
                    </td>

                    <td align="center" valign="bottom">
                        @if (!$user->is_admin)
                            <a href="{{ url('admin/users/driverstatus/' . base64_encode($user->id) . '/' . ($user->is_driver == 1 ? 0 : 1)) }}"
                                onclick="return confirm('Update Driver?')">
                                <img src="{{ asset($user->is_driver == 1 ? 'img/green2.jpg' : 'img/red3.jpg') }}"
                                    alt="Driver">
                            </a>
                        @endif
                    </td>

                    <td align="center" valign="bottom">
                        @if (!$user->is_admin)
                            @if ($user->is_dealer == 1)
                                <a href="{{ url('admin/users/dealer_approve/' . base64_encode($user->id) . '/2') }}"
                                    onclick="return confirm('Reject dealer?')">
                                    <img src="{{ asset('img/green2.jpg') }}">
                                </a>
                            @elseif($user->is_dealer == 2)
                                <a href="{{ url('admin/users/dealer_approve/' . base64_encode($user->id) . '/1') }}"
                                    onclick="return confirm('Approve dealer?')">
                                    <i class='fa fa-frown-o fa-2x'></i>
                                </a>
                            @else
                                <img src="{{ asset('img/red3.jpg') }}" alt="Dealer">
                            @endif
                        @endif
                    </td>

                    <td align="center" valign="bottom">
                        @if (!$user->is_admin)
                            @php
                                $checkrUrl = url('admin/users/checkr_status/' . base64_encode($user->id));
                            @endphp
                            @switch($user->checkr_status)
                                @case(1)
                                    <img src="{{ asset('img/green2.jpg') }}" title="Approved">
                                @break

                                @case(0)
                                    <a href="{{ $checkrUrl }}" onclick="return confirm('Process Checkr?')">
                                        <i class="icon-hand"></i>
                                    </a>
                                @break

                                @case(2)
                                    <a href="{{ $checkrUrl }}">
                                        <i class="icon-spinner4"></i>
                                    </a>
                                @break

                                @case(5)
                                    <a href="{{ $checkrUrl }}">
                                        <i class="icon-unfold"></i>
                                    </a>
                                @break

                                @case(3)
                                @case(4)
                                    <a href="javascript:;">
                                        <i class="icon-blocked"></i>
                                    </a>
                                @break

                                @default
                                    <a href="{{ $checkrUrl }}">
                                        <img src="{{ asset('img/red3.jpg') }}">
                                    </a>
                            @endswitch
                        @endif
                    </td>

                    <td align="center" valign="bottom">
                        @if (!$user->is_admin)
                            <a href="{{ url('admin/users/trash/' . base64_encode($user->id) . '/' . ($user->trash == 1 ? 0 : 1)) }}"
                                onclick="return confirm('Are you sure?')">
                                <img src="{{ asset($user->trash == 1 ? 'img/red3.jpg' : 'img/green2.jpg') }}"
                                    title="Delete Toggle">
                            </a>
                        @endif
                    </td>

                    <td align="center">
                        <ul class="icons-list">
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                    <i class="icon-menu9"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li>
                                        <a href="{{ url('admin/users/view', base64_encode($user->id)) }}">
                                            <i class="glyphicon glyphicon-zoom-in"></i>
                                            {{ 'View' }}
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ url('admin/users/add', base64_encode($user->id)) }}">
                                            <i class="glyphicon glyphicon-pencil"></i>
                                            {{ 'Edit' }}
                                        </a>
                                    </li>

                                    @if (!$user->is_admin)
                                        <li>
                                            <a href="{{ url('admin/user_ccs/index', base64_encode($user->id)) }}">
                                                <i class="glyphicon glyphicon-credit-card"></i>
                                                {{ 'Credit Cards' }}
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ url('admin/wallet/index', base64_encode($user->id)) }}">
                                                <i class="glyphicon glyphicon-usd"></i>    
                                                    {{'Wallet'}}
                                            </a>
                                        </li>
                                         <li>
                                            <a href="{{ url('admin/accounting/reports/index', $user->id) }}">
                                                <i class="icon-file-stats2"></i>    
                                                    {{'Accounting Report'}}
                                            </a>
                                        </li>
                                         <li>
                                            <a href="{{ url('admin/user_note/user_notes/index', $user->id) }}">
                                                <i class="icon-file-stats"></i>    
                                                    {{'User Notes'}}
                                            </a>
                                        </li>

                                        @if ($user->is_owner)
                                            <li>    
                                                <a href="{{ url('admin/users/bankdetails', base64_encode($user->id)) }}">
                                                    <i class="glyphicon glyphicon-sound-dolby"></i> 
                                                        {{'Bank Details'}}
                                                </a>
                                            </li>
                                            <li>    
                                                <a href="{{ url('admin/users/revsetting', base64_encode($user->id)) }}">
                                                    <i class="glyphicon glyphicon-certificate"></i> 
                                                        {{'Revenue Setting'}}
                                                </a>
                                            </li>
                                            <li>    
                                                <a href="{{ url('admin/agreement_template/agreement_templates/admin_index', base64_encode($user->id)) }}">
                                                    <i class="icon-file-stats"></i> 
                                                        {{'Agreement Templates'}}
                                                </a>
                                            </li>
                                            <li>    
                                                <a href="{{ url('admin/insurance_templates/admin_index', base64_encode($user->id)) }}">
                                                    <i class="glyphicon  glyphicon-list-alt"></i> 
                                                        {{'Insurance'}}
                                                </a>
                                            </li>
                                            <li>    
                                                <a href="{{ url('admin/deposit_templates/admin_index', base64_encode($user->id)) }}">
                                                    <i class="glyphicon  glyphicon-usd"></i> 
                                                        {{'Payment Setting Template'}}
                                                </a>
                                            </li>
                                            <li>    
                                                <a href="{{ url('admin/customer_balances/admin_subscription', base64_encode($user->id)) }}">
                                                    <i class="glyphicon  glyphicon-usd"></i> 
                                                        {{'Credits and Debits'}}
                                                </a>
                                            </li>
                                            <li>    
                                                <a href="{{ url('admin/settings/index', base64_encode($user->id)) }}">
                                                    <i class="icon-gear"></i> 
                                                        {{'General Setting'}}
                                                </a>
                                            </li>
                                            <li>    
                                                <a href="{{ url('admin/eland/settings/index', base64_encode($user->id)) }}">
                                                    <i class="icon-gear"></i> 
                                                        {{'Eland Setting'}}
                                                </a>
                                            </li>
                                            <li>    
                                                <a href="{{ url('admin/vehicle/list_unlist_rules/index', $user->id) }}">
                                                    <i class="icon-gear"></i> 
                                                        {{'Vehicle List/Unlist Setting'}}
                                                </a>
                                            </li>

                                        @endif

                                        @if($user->bank)
                                            <li>
                                                <a href="{{ url('admin/plaid_users/index', base64_encode($user->id)) }}">
                                                    <i class="icon icon-dribbble3"></i> 
                                                        {{'Connected Bank Accounts'}}
                                                </a>
                                            </li>
                                        @endif

                                        @if($user->is_driver)
                                            <li>
                                                <a href="{{ url('admin/loan/managers/detail', base64_encode($user->id)) }}">
                                                    <i class="icon icon-bag"></i> 
                                                    {{'Loan Stipulations'}}
                                                </a>
                                            </li>
                                        @endif

                                        <li>
                                            <a href="{{ url('admin/users/change_phone', base64_encode($user->id)) }}">
                                                <i class="icon icon-iphone"></i> 
                                                {{'Change Phone#'}}
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        </ul>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@include('legacy.elements.dispacher.paging_box', ['paginator' => $users, 'limit' => $limit])
