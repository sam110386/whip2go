@extends('layouts.main')

@section('title', 'Manage Staff Users')
@section('header_title', 'Staff users')

@section('content')
    <div class="panel">
        <section class="reportListingHeading" style="margin-bottom: 12px;">
            <h2 style="display:inline-block; margin: 0 16px 0 0;">Search staff user</h2>
            <a href="/staff_users/add" class="label label-success" style="display:inline-block; padding:6px 10px; background:#5cb85c; color:#fff; text-decoration:none; border-radius:3px;">Add new</a>
        </section>

        @if(session('success'))<p style="color:green;">{{ session('success') }}</p>@endif
        @if(session('error'))<p style="color:red;">{{ session('error') }}</p>@endif

        <form method="get" action="/staff_users/index" id="frmSearchadmin">
            <div class="row" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
                <div>
                    <label>Keyword<br>
                        <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" style="min-width:200px;">
                    </label>
                </div>
                <div>
                    <label>Status<br>
                        <select name="Search[show]" class="form-control" style="min-width:140px;">
                            <option value="">Select..</option>
                            <option value="Active" @if(strcasecmp($show, 'Active')===0) selected @endif>Active</option>
                            <option value="Deactive" @if(strcasecmp($show, 'Deactive')===0) selected @endif>Inactive</option>
                        </select>
                    </label>
                </div>
                <div>
                    <label>Per page<br>
                        <select name="Record[limit]" class="form-control">
                            @foreach([25, 50, 100, 200] as $opt)
                                <option value="{{ $opt }}" @if((int)$limit === $opt) selected @endif>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </div>
        </form>

        <div style="height:16px;"></div>

        @if(!$subusers->isEmpty())
            <table width="100%" cellpadding="6" class="table table-responsive" border="0">
                <thead>
                <tr>
                    <th>#</th>
                    <th>First name</th>
                    <th>Last name</th>
                    <th>Email</th>
                    <th>Contact#</th>
                    <th>Created</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($subusers as $i => $user)
                    <tr>
                        <td>{{ $subusers->firstItem() + $i }}</td>
                        <td>{{ $user->first_name }}</td>
                        <td>{{ $user->last_name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->contact_number }}</td>
                        <td>{{ $user->created }}</td>
                        <td align="center">
                            @if((int)$user->status === 1)
                                <a href="/staff_users/status/{{ base64_encode((string)$user->id) }}/0" onclick="return confirm('Are you sure to update this Staff?');" title="Deactivate">●</a>
                            @else
                                <a href="/staff_users/status/{{ base64_encode((string)$user->id) }}/1" onclick="return confirm('Are you sure to update this Staff?');" title="Activate">○</a>
                            @endif
                        </td>
                        <td class="action">
                            <a href="/staff_users/view/{{ base64_encode((string)$user->id) }}">View</a>
                            ·
                            <a href="/staff_users/add/{{ base64_encode((string)$user->id) }}">Edit</a>
                            ·
                            <a href="/staff_users/delete/{{ base64_encode((string)$user->id) }}" onclick="return confirm('Delete this staff user?');">Delete</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            {{ $subusers->appends(['Search' => ['keyword' => $keyword, 'show' => $show], 'Record' => ['limit' => $limit]])->links() }}
        @else
            <p>No record found.</p>
        @endif
    </div>
@endsection
