@php
    $emailTemplates ??= null;
    $keyword ??= '';
    $searchin ??= '';
    $show ??= '';
    $limit ??= 25;
@endphp

@if($emailTemplates === null)
    <p>Email templates are not available.</p>
@elseif($emailTemplates->total() === 0)
    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
        <tr><td align="center">No record found</td></tr>
    </table>
@else
    <form method="post" action="/admin/email_templates/multiplAction" id="frmEmailTemplates" name="frmEmailTemplates">
        @csrf
        <input type="hidden" name="Search[keyword]" value="{{ e($keyword) }}">
        <input type="hidden" name="Search[searchin]" value="{{ e($searchin) }}">
        <input type="hidden" name="Search[show]" value="{{ e($show) }}">

        <div class="table-responsive">
            <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive table-bordered">
                <thead>
                    <tr>
                        @include('partials.dispacher.sortable_header', ['columns' => [
                            ['title' => '<input type="checkbox" id="selectall" onclick="toggleAll(this)"/>', 'sortable' => false, 'style' => 'width:28px;'],
                            ['title' => 'Title', 'field' => 'head_title'],
                            ['title' => 'Subject', 'field' => 'subject'],
                            ['title' => 'Type', 'field' => 'type'],
                            ['title' => 'Status', 'field' => 'status'],
                            ['title' => 'Modified', 'field' => 'modified'],
                            ['title' => 'Action', 'sortable' => false, 'style' => 'width:120px;']
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
                            'keyword' => $keyword,
                            'searchin' => $searchin,
                            'showtype' => $show,
                        ], fn($v) => $v !== null && $v !== ''));
                        $qSuffix = $q !== '' ? ('?' . $q) : '';
                    @endphp
                    <tr>
                        <td><input type="checkbox" name="select[]" value="{{ $eid }}"></td>
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

        @include('partials.dispacher.paging_box', ['paginator' => $emailTemplates, 'limit' => $limit])

        <div style="margin:12px 0;">
            <button type="submit" name="EmailTemplate[submit]" value="active" class="btn btn-default btn-sm">Active</button>
            <button type="submit" name="EmailTemplate[submit]" value="inactive" class="btn btn-default btn-sm">Inactive</button>
            <button type="submit" name="EmailTemplate[submit]" value="del" class="btn btn-default btn-sm" onclick="return confirm('Delete selected records?');">Delete</button>
        </div>
    </form>

    <p class="legends" style="margin-top:12px;">
        <b>Legends:</b>
        <i class="icon-clipboard3"></i>&nbsp;View&nbsp;
        <i class="icon-pencil"></i>&nbsp;Edit&nbsp;
        <i class="icon-trash"></i>&nbsp;Delete
    </p>
@endif
