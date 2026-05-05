@php
    $pages ??= [];
    $limit ??= 50;
@endphp

<div class="table-responsive">
    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => 'ID', 'field' => 'id'],
                    ['title' => 'Title', 'field' => 'title'],
                    ['title' => 'Code', 'field' => 'pagecode'],
                    ['title' => 'Status', 'field' => 'status'],
                    ['title' => 'Actions', 'sortable' => false]
                ]])
            </tr>
        </thead>
        <tbody>
            @forelse($pages as $page)
                <tr>
                    <td>{{ $page->id }}</td>
                    <td>{{ $page->title }}</td>
                    <td>{{ $page->pagecode }}</td>
                    <td>{{ (int)($page->status ?? 0) === 1 ? 'Active' : 'Inactive' }}</td>
                    <td>
                        <a href="/admin/pages/view/{{ $page->id }}" title="View"><i class="icon-clipboard3"></i></a>
                        <a href="/admin/pages/add/{{ $page->id }}" title="Edit"><i class="icon-pencil"></i></a>
                        <a href="/admin/pages/status/{{ $page->id }}/{{ (int)($page->status ?? 0) === 1 ? 0 : 1 }}" title="Toggle Status"><i class="icon-sync"></i></a>
                        <a href="/admin/pages/delete/{{ $page->id }}" onclick="return confirm('Delete page?')" title="Delete"><i class="icon-trash"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" align="center">No record found</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.dispacher.paging_box', ['paginator' => $pages, 'limit' => $limit])
