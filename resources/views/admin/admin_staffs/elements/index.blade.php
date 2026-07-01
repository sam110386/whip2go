@php
    $keyword ??= '';
    $fieldname ??= '';
    $show ??= null;
    $users ??= [];
    $limit ??= 50;
    $columns = [
        ['title' => '<input type="checkbox" name="selectall" id="selectAllChildCheckboxs" value="1" onclick="GetAction(this.checked, \'this.form.data[pageListing][select]\', document.querySelectorAll(\'input[id=select1]\'))"/>', 'sortable' => false, 'html' => true],
        ['title' => '#', 'field' => 'id'],
        ['title' => 'Username', 'sortable' => false],
        ['title' => 'First Name', 'sortable' => false],
        ['title' => 'Last Name', 'sortable' => false],
        ['title' => 'Email', 'sortable' => false],
        ['title' => 'Contact#', 'sortable' => false],
        ['title' => 'Created', 'field' => 'created'],
        ['title' => 'Status', 'field' => 'status'],
        ['title' => 'Role', 'sortable' => false],
        ['title' => 'Actions', 'sortable' => false]
    ];
@endphp

<form method="GET" action="/admin/admin_staffs/multiplAction" name="frm1" id="frm1"
    onsubmit="return ischeckboxSelected(frm1,frm1.select1,'Admin')">
    @csrf

    <input type="hidden" name="Search[keyword]" value="{{ $keyword }}">
    <input type="hidden" name="Search[searchin]" value="{{ $fieldname }}">
    <input type="hidden" name="Search[show]" value="{{ $show }}">

    <div class="table-responsive">
        <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
            <thead>
                <tr>
                    @include('partials.dispacher.sortable_header', compact('columns'))
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <input type="checkbox" name="select[{{ data_get($user, 'id', '') }}]"
                                value="{{ data_get($user, 'id', '') }}" id="select1" style="border:0;">
                        </td>
                        <td>
                            {{ data_get($user, 'id', '') }}
                        </td>
                        <td>
                            {{ data_get($user, 'username', '') }}
                        </td>
                        <td>
                            {{ data_get($user, 'first_name', '') }}
                        </td>
                        <td>
                            {{ data_get($user, 'last_name', '') }}
                        </td>
                        <td>
                            {{ data_get($user, 'email', '') }}
                        </td>
                        <td>
                            {{ data_get($user, 'contact_number', '') }}
                        </td>
                        <td>
                            {{ data_get($user, 'created', '') }}
                        </td>
                        <td align="center">
                            @if(data_get($user, 'status') == 1)
                                <a href="{{ url('admin/admin_staffs/status/' . base64_encode(data_get($user, 'id', '')) . '/0')}}"
                                    onclick="return confirm('Deactivate this user?');">
                                    <img src="{{ legacy_asset('img/green2.jpg') }}" alt="Status" title="Status">
                                </a>
                            @else
                                <a href="{{ url('admin/admin_staffs/status/' . base64_encode(data_get($user, 'id', '')) . '/1')}}"
                                    onclick="return confirm('Activate this user?');">
                                    <img src="{{ legacy_asset('img/red3.jpg') }}" alt="Status" title="Status">
                                </a>
                            @endif
                        </td>
                        <td>
                            {{ data_get($user, 'role.name', '--') }}
                        </td>
                        <td>
                            <a href="{{ url('admin/admin_staffs/add/' . base64_encode(data_get($user, 'id', ''))) }}">
                                <i class='glyphicon glyphicon-edit'></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" align="center">No record found</td>
                    </tr>
                @endforelse
                <tr>
                    <td colspan="11" style="padding-left:7px;text-align:left;">
                        <strong>Activate/Deactivate/Delete multiple Staff:</strong>
                        <select name="User[status]" id="AdminStatus" class="select">
                            <option value="">--Select..</option>
                            <option value="active">Activate</option>
                            <option value="inactive">Inactive</option>
                            <option value="del">Delete</option>
                        </select>
                        <button type="submit" class="btn btn-primary" alt="Multiple Status"
                            title="Multiple Status">Submit</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</form>

@include('partials.dispacher.paging_box', ['paginator' => $users, 'limit' => $limit])