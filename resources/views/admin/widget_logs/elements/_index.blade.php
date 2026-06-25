@php
    $files ??= [];
    $limit ??= 50;
@endphp

@include('partials.dispacher.paging_box', ['paginator' => $files, 'limit' => $limit, 'position' => 'top'])

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
                        <a href="javascript:void(0)" title="View Record" onclick="WidgetLogView('{{ $file['filename'] }}')">
                            <i class="glyphicon glyphicon-zoom-in"></i>
                        </a>
                        <a href="javascript:void(0)" title="Delete Record"
                            onclick="WidgetLogDelete('{{ $file['filename'] }}')">
                            <i class="icon-trash"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@include('partials.dispacher.paging_box', ['paginator' => $files, 'limit' => $limit])