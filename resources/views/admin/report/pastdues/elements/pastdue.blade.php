@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50,'position' => 'top'])
@endif

<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => '#', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Extended Date', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Note', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Cycle Ext(s)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Total Ext(s)', 'style' => 'text-align:center;', 'sortable' => false],
                ]])
            </tr>
        </thead>
        <tbody>
            @foreach ($lists ?? [] as $list)
                <tr id="{{ $list->id ?? '' }}">
                    <td style="text-align:center;">
                        {{ $list->increment_id ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        @php
                            $dzt = session('default_timezone', config('app.timezone'));
                            $ex0 = isset($list->orderExtlogs) && $list->orderExtlogs->first() ? $list->orderExtlogs->first() : null;
                            $exd = $ex0->ext_date ?? '';
                            echo ($ex0 && $exd != '' && $exd != '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($exd)->timezone($dzt)->format('m/d/Y h:i A') : '--';
                        @endphp
                    </td>
                    <td style="text-align:center;">
                        {{ $list->orderExtlogs->first()?->note ?? '-' }}
                    </td>
                    <td style="text-align:center;">
                        <a href="javascript:;" onclick="ShowPastDueLogs({{ $list->id ?? 0 }})">
                        {{ \App\Helpers\Legacy\ReportHelper::getExtCount($list->id ?? 0) }}
                    </a>
                    </td>
                    <td style="text-align:center;">
                        {{ \App\Helpers\Legacy\ReportHelper::getExtParentWithSiblingCount((!empty($list->parent_id) ? $list->parent_id : ($list->id ?? 0))) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
