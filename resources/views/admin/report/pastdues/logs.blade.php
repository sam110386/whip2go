@php $defaultTz = session('default_timezone', config('app.timezone')); @endphp
<div class="item">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">Booking#</th>
                <th style="text-align:center;">Extended Date</th>
                <th style="text-align:center;">Note</th>
                <th style="text-align:center;">By</th>
                <th style="text-align:center;">Count Towards Extension</th>
                <th style="text-align:center;">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lists as $list)
                <tr>
                    <td style="text-align:center;">
                        {{ $list['CsOrder']['increment_id'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        @php
                            $exd = $list['OrderExtlog']['ext_date'] ?? '';
                            echo ($exd != '' && $exd != '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($exd)->timezone($defaultTz)->format('m/d/Y h:i A') : '--';
                        @endphp
                    </td>
                    <td style="text-align:center;">
                        {{ $list['OrderExtlog']['note'] ?? '-' }}
                    </td>
                    <td style="text-align:center;">
                        {{ ($list['Owner']['first_name'] ?? '') . ' ' . ($list['Owner']['last_name'] ?? '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ ($list['OrderExtlog']['admin_count'] ?? 0) == 0 ? 'Yes' : 'No' }}
                    </td>
                    <td style="text-align:center;">
                        @php
                            $crd = $list['OrderExtlog']['created'] ?? '';
                            echo ($crd != '' && $crd != '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($crd)->timezone($defaultTz)->format('m/d/Y h:i A') : '--';
                        @endphp
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
