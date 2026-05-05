@php
    $portfolioSvc = app(\App\Services\Legacy\Report\PortfolioService::class);
@endphp
@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => '#', 'field' => 'increment_id', 'style' => 'text-align:center;'],
                    ['title' => 'Extended Date', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Note', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Cycle Ext(s)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Total Ext(s)', 'style' => 'text-align:center;', 'sortable' => false],
                ]])
            </tr>
        </thead>
        <tbody>
            @foreach ($lists ?? [] as $list)
                <tr id="{{ $list['CsOrder']['id'] ?? '' }}">
                    <td style="text-align:center;">
                        {{ $list['CsOrder']['increment_id'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        @php
                            $dzt = session('default_timezone', config('app.timezone'));
                            $ex0 = isset($list['OrderExtlog'][0]) && is_array($list['OrderExtlog'][0]) ? $list['OrderExtlog'][0] : null;
                            $exd = $ex0['ext_date'] ?? '';
                            echo ($ex0 && $exd != '' && $exd != '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($exd)->timezone($dzt)->format('m/d/Y h:i A') : '--';
                        @endphp
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($list, 'OrderExtlog.0.note') ?: '-' }}
                    </td>
                    <td style="text-align:center;">
                        <a href="javascript:;" onclick="ShowPastDueLogs({{ $list['CsOrder']['id'] ?? 0 }})">{{ $portfolioSvc->getExtCount($list['CsOrder']['id'] ?? 0) }}</a>
                    </td>
                    <td style="text-align:center;">
                        {{ $portfolioSvc->getExtParentWithSiblingCount((!empty($list['CsOrder']['parent_id']) ? $list['CsOrder']['parent_id'] : ($list['CsOrder']['id'] ?? 0))) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
