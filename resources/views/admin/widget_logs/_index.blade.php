@if(!empty($paging) && $paging['pageCount'] > 1)
<section class="pagging">
    <ul class="pagination pagination-rounded pull-right">
        @if($paging['prevPage'])
            <li><a href="{{ url('/admin/widget_logs?page=' . ($paging['page'] - 1)) }}">Previous</a></li>
        @else
            <li class="disabled"><a href="javascript:;">Previous</a></li>
        @endif
        @for($i = 1; $i <= $paging['pageCount']; $i++)
            <li class="{{ $i == $paging['page'] ? 'active' : '' }}"><a href="{{ url('/admin/widget_logs?page=' . $i) }}">{{ $i }}</a></li>
        @endfor
        @if($paging['nextPage'])
            <li><a href="{{ url('/admin/widget_logs?page=' . ($paging['page'] + 1)) }}">Next</a></li>
        @else
            <li class="disabled"><a href="javascript:;">Next</a></li>
        @endif
    </ul>
</section>
@endif

<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">File</th>
                <th style="text-align:center;">Date</th>
                <th style="text-align:center;">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($files as $file)
                <tr>
                    <td style="text-align:center;">
                        {{ $file['filename'] }}
                    </td>
                    <td style="text-align:center;">
                        {{ $file['date'] }}
                    </td>
                    <td style="text-align:center;">
                        <a href="javascript:;" title="View Record" onclick="WidgetLogView('{{ $file['filename'] }}')"><i class="glyphicon glyphicon-zoom-in"></i></a>
                        <a href="javascript:;" title="Delete Record" onclick="WidgetLogDelete('{{ $file['filename'] }}')"><i class="icon-trash"></i></a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if(!empty($paging) && $paging['pageCount'] > 1)
<section class="pagging">
    <ul class="pagination pagination-rounded pull-right">
        @if($paging['prevPage'])
            <li><a href="{{ url('/admin/widget_logs?page=' . ($paging['page'] - 1)) }}">Previous</a></li>
        @else
            <li class="disabled"><a href="javascript:;">Previous</a></li>
        @endif
        @for($i = 1; $i <= $paging['pageCount']; $i++)
            <li class="{{ $i == $paging['page'] ? 'active' : '' }}"><a href="{{ url('/admin/widget_logs?page=' . $i) }}">{{ $i }}</a></li>
        @endfor
        @if($paging['nextPage'])
            <li><a href="{{ url('/admin/widget_logs?page=' . ($paging['page'] + 1)) }}">Next</a></li>
        @else
            <li class="disabled"><a href="javascript:;">Next</a></li>
        @endif
    </ul>
</section>
@endif
