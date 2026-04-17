@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Manage Email Templates')

@section('content')
<div class="panel">
    <section class="right_content">
        @if(session('success'))
            <div class="alert alert-success" style="margin-bottom:10px;">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger" style="margin-bottom:10px;">{{ session('error') }}</div>
        @endif

        <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
            <tr>
                <td class="adminGridHeading heading" colspan="2"><h3 style="margin:10px 0;">{{ $listTitle ?? 'Manage Email Templates' }}</h3></td>
            </tr>
            <tr><td colspan="2">&nbsp;</td></tr>
            <tr>
                <td colspan="2">
                    <table width="100%" cellspacing="0" cellpadding="0" align="center" border="0">
                        <tr class="adminBoxHeading">
                            <td height="25" class="reportListingHeading">Search Email Template / Reminder</td>
                        </tr>
                        <tr>
                            <td>
                                <table width="100%" cellspacing="1" cellpadding="2" class="adminBox" align="center" border="0">
                                    <tr>
                                        <td>
                                            <form method="get" action="/admin/email_templates/index" id="frmSearchadmin" name="frmSearchadmin">
                                                <table width="100%" cellspacing="1" cellpadding="1" align="center" border="0">
                                                    <tr>
                                                        <td align="left" width="9%">Keyword :</td>
                                                        <td width="30%">
                                                            <input type="text" name="Search[keyword]" class="form-control textbox" size="30" maxlength="50"
                                                                   value="{{ e($keyword ?? '') }}"/>
                                                        </td>
                                                        <td width="20%">Search By:&nbsp;
                                                            <select name="Search[searchin]" class="form-control textbox">
                                                                <option value="">Select..</option>
                                                                @foreach(($options ?? []) as $k => $label)
                                                                    <option value="{{ $k }}" @selected(($searchin ?? '') === $k)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td width="20%">
                                                            <select name="Search[show]" class="form-control textbox">
                                                                <option value="">Select..</option>
                                                                <option value="1" @selected((string)($show ?? '') === '1')>Email Template</option>
                                                                <option value="2" @selected((string)($show ?? '') === '2')>Reminder</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <button type="submit" name="search" value="search" class="btn btn-primary btn_53">Search</button>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </form>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2" id="pagging">
                    @if($emailTemplates === null)
                        <p>Email templates are not available.</p>
                    @elseif($emailTemplates->total() === 0)
                        <table width="100%" cellpadding="1" cellspacing="1" border="0" class="borderTable">
                            <tr><td align="center">No record found</td></tr>
                        </table>
                    @else
                        <form method="post" action="/admin/email_templates/multiplAction" id="frmEmailTemplates" name="frmEmailTemplates">
                            @csrf
                            <input type="hidden" name="Search[keyword]" value="{{ e($keyword ?? '') }}"/>
                            <input type="hidden" name="Search[searchin]" value="{{ e($searchin ?? '') }}"/>
                            <input type="hidden" name="Search[show]" value="{{ e($show ?? '') }}"/>

                            <div class="table-responsive">
                                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive table-bordered">
                                    <thead>
                                        <tr class="adminBoxHeading">
                                            @include('partials.dispacher.sortable_header', ['columns' => [
                                                ['field' => 'checkbox', 'title' => '<input type="checkbox" id="selectall" onclick="toggleAll(this)"/>', 'sortable' => false, 'style' => 'width:28px;'],
                                                ['field' => 'head_title', 'title' => 'Title'],
                                                ['field' => 'subject', 'title' => 'Subject'],
                                                ['field' => 'type', 'title' => 'Type'],
                                                ['field' => 'status', 'title' => 'Status'],
                                                ['field' => 'modified', 'title' => 'Modified'],
                                                ['field' => 'actions', 'title' => 'Action', 'sortable' => false, 'style' => 'width:120px;']
                                            ]])
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($emailTemplates as $row)
                                        @php
                                            $eid = (int)($row->id ?? 0);
                                            $b64 = base64_encode((string)$eid);
                                            $stype = (int)($row->type ?? 0);
                                            $typeLabel = $stype === 2 ? 'Reminder' : 'Email Template';
                                            $isActive = (int)($row->status ?? 0) === 1;
                                            $q = http_build_query(array_filter([
                                                'keyword' => $keyword ?? '',
                                                'searchin' => $searchin ?? '',
                                                'showtype' => $show ?? '',
                                            ], fn($v) => $v !== null && $v !== ''));
                                            $qSuffix = $q !== '' ? ('?' . $q) : '';
                                        @endphp
                                        <tr>
                                            <td><input type="checkbox" name="select[]" value="{{ $eid }}"/></td>
                                            <td>{{ e($row->head_title ?? '') }}</td>
                                            <td>{{ e($row->subject ?? '') }}</td>
                                            <td>{{ $typeLabel }}</td>
                                            <td>{{ $isActive ? 'Active' : 'Inactive' }}</td>
                                            <td>
                                                @if(!empty($row->modified) && $row->modified !== '0000-00-00 00:00:00')
                                                    {{ \Carbon\Carbon::parse($row->modified)->format('m/d/Y h:i A') }}
                                                @endif
                                            </td>
                                            <td>
                                                <a href="/admin/email_templates/view/{{ $b64 }}{{ $qSuffix }}" title="View"><i class="icon-clipboard3"></i></a>
                                                <a href="/admin/email_templates/add/{{ $b64 }}" title="Edit"><i class="icon-pencil"></i></a>
                                                <a href="/admin/email_templates/status/{{ $b64 }}/{{ $isActive ? '1' : '0' }}{{ $qSuffix }}" title="{{ $isActive ? 'Deactivate' : 'Activate' }}">
                                                    <i class="icon-{{ $isActive ? 'cross2' : 'checkmark' }}"></i>
                                                </a>
                                                <a href="/admin/email_templates/delete/{{ $b64 }}" title="Delete" onclick="return confirm('Delete this template?');"><i class="icon-trash"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @include('partials.dispacher.paging_box', ['paginator' => $emailTemplates, 'limit' => $limit ?? 25])
                            
                            <div style="margin:12px 0;">
                                <button type="submit" name="EmailTemplate[submit]" value="active" class="btn btn-default btn-sm">Active</button>
                                <button type="submit" name="EmailTemplate[submit]" value="inactive" class="btn btn-default btn-sm">Inactive</button>
                                <button type="submit" name="EmailTemplate[submit]" value="del" class="btn btn-default btn-sm" onclick="return confirm('Delete selected records?');">Delete</button>
                            </div>
                        </form>
                    @endif
                </td>
            </tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
                <td class="legends" colspan="2">
                    <b>Legends:</b>
                    <i class="icon-clipboard3"></i>&nbsp;View&nbsp;
                    <i class="icon-pencil"></i>&nbsp;Edit&nbsp;
                    <i class="icon-trash"></i>&nbsp;Delete
                </td>
            </tr>
        </table>
    </section>
</div>
@endsection

@push('scripts')
<script>
function toggleAll(master) {
    var boxes = document.querySelectorAll('input[name="select[]"]');
    for (var i = 0; i < boxes.length; i++) {
        boxes[i].checked = master.checked;
    }
}
</script>
@endpush
