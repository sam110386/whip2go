<div class="table-responsive">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th valign="top">{{ '#' }}</th>
                <th valign="top">{{ 'First Name' }}</th>
                <th valign="top">{{ 'Last Name' }}</th>
                <th valign="top" style="width: 30px;">{{ 'Email' }}</th>
                <th valign="top">{{ 'Contact#' }}</th>
                <th valign="top">{{ 'Created' }}</th>
                <th valign="top">{{ 'Status' }}</th>
                <th valign="top">{{ 'Verified' }}</th>
                <th valign="top">{{ 'Renter' }}</th>
                <th valign="top">{{ 'Driver' }}</th>
                <th valign="top">{{ 'Dealer' }}</th>
                <th valign="top">{{ 'Checkr Status' }}</th>
                <th valign="top">{{ 'Deleted' }}</th>
                <th valign="top">{{ 'Actions' }}</th>
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
                    <td valign="top">{{ $user->created }}</td>

                    <td align="center" valign="bottom">
                        <a href="{{ url('admin.users.status', [base64_encode($user->id), $user->status == 1 ? 0 : 1]) }}"
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
                                <a href="{{ url('admin.users.verify', base64_encode($user->id)) }}"
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
                            <a href="{{ url('admin.users.driverstatus', [base64_encode($user->id), $user->is_driver == 1 ? 0 : 1]) }}"
                                onclick="return confirm('Update Driver?')">
                                <img src="{{ asset($user->is_driver == 1 ? 'img/green2.jpg' : 'img/red3.jpg') }}"
                                    alt="Driver">
                            </a>
                        @endif
                    </td>

                    <td align="center" valign="bottom">
                        @if (!$user->is_admin)
                            @if ($user->is_dealer == 1)
                                <a href="{{ url('admin.users.dealer_approve', [base64_encode($user->id), 2]) }}"
                                    onclick="return confirm('Reject dealer?')">
                                    <img src="{{ asset('img/green2.jpg') }}">
                                </a>
                            @elseif($user->is_dealer == 2)
                                <a href="{{ url('admin.users.dealer_approve', [base64_encode($user->id), 1]) }}"
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
                                $checkrUrl = url('admin.users.checkr_status', base64_encode($user->id));
                            @endphp
                            @switch($user->checkr_status)
                                @case(1)
                                    <img src="{{ asset('img/green2.jpg') }}" title="Approved">
                                @break

                                @case(0)
                                    <a href="{{ $checkrUrl }}" onclick="return confirm('Process Checkr?')"><i
                                            class="icon-hand"></i></a>
                                @break

                                @case(2)
                                    <a href="{{ $checkrUrl }}"><i class="icon-spinner4"></i></a>
                                @break

                                @case(5)
                                    <a href="{{ $checkrUrl }}"><i class="icon-unfold"></i></a>
                                @break

                                @case(3)
                                @case(4)
                                    <a href="javascript:;"><i class="icon-blocked"></i></a>
                                @break

                                @default
                                    <a href="{{ $checkrUrl }}"><img src="{{ asset('img/red3.jpg') }}"></a>
                            @endswitch
                        @endif
                    </td>

                    {{-- Trash/Deleted Column --}}
                    <td align="center" valign="bottom">
                        @if (!$user->is_admin)
                            <a href="{{ url('admin.users.trash', [base64_encode($user->id), $user->trash == 1 ? 0 : 1]) }}"
                                onclick="return confirm('Are you sure?')">
                                <img src="{{ asset($user->trash == 1 ? 'img/red3.jpg' : 'img/green2.jpg') }}"
                                    title="Delete Toggle">
                            </a>
                        @endif
                    </td>

                    {{-- Actions Dropdown --}}
                    <td align="center">
                        <ul class="icons-list">
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i
                                        class="icon-menu9"></i></a>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="{{ url('admin.users.view', base64_encode($user->id)) }}"><i
                                                class="glyphicon glyphicon-zoom-in"></i> View</a></li>
                                    <li><a href="{{ url('admin.users.edit', base64_encode($user->id)) }}"><i
                                                class="glyphicon glyphicon-pencil"></i> Edit</a></li>

                                    @if (!$user->is_admin)
                                        <li><a href="{{ url('admin.user_ccs.index', base64_encode($user->id)) }}"><i
                                                    class="glyphicon glyphicon-credit-card"></i> Credit Cards</a></li>
                                        <li><a href="{{ url('admin.wallet.index', base64_encode($user->id)) }}"><i
                                                    class="glyphicon glyphicon-usd"></i> Wallet</a></li>

                                        @if ($user->is_owner)
                                            <li><a
                                                    href="{{ url('admin.users.bankdetails', base64_encode($user->id)) }}"><i
                                                        class="glyphicon glyphicon-sound-dolby"></i> Bank Details</a>
                                            </li>
                                            {{-- Add other owner-specific links here following the same pattern --}}
                                        @endif

                                        <li><a href="{{ url('admin.users.change_phone', base64_encode($user->id)) }}"><i
                                                    class="icon icon-iphone"></i> Change Phone#</a></li>
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

<div class="mt-3">
    {{ $users->links() }}
</div>
