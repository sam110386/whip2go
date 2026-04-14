<!-- Simple list -->
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">#</th>
                <th style="text-align:center;">Customer</th>
                <th style="text-align:center;">Phone#</th>
                <th style="text-align:center;">Income</th>
                <th style="text-align:center;">Pay Stubs</th>
                <th style="text-align:center;">Utility Bill</th>
                <th style="text-align:center;">Status</th>
                <th style="text-align:center;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lists as $list)
                <tr>
                    <td style="text-align:center;">{{ $list->id }}</td>
                    <td style="text-align:center;">{{ $list->first_name }} {{ $list->last_name }}</td>
                    <td style="text-align:center;">{{ $list->contact_number }}</td>
                    <td style="text-align:center;">{{ $list->income }}</td>
                    <td style="text-align:center;">
                        @if(!empty($list->pay_stub))
                            <a href="{{ config('app.url') }}files/userdocs/{{ $list->pay_stub }}" title="Pay Stub Doc" class="fancybox"><i class="icon-magazine"></i></a>
                        @endif
                        @if(!empty($list->pay_stub_2))
                            <a href="{{ config('app.url') }}files/userdocs/{{ $list->pay_stub_2 }}" title="Pay Stub Doc" class="fancybox"><i class="icon-magazine"></i></a>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if(!empty($list->utility_bill))
                            <a href="{{ config('app.url') }}files/userdocs/{{ $list->utility_bill }}" title="Utility Bill" class="fancybox"><i class="icon-magazine"></i></a>
                        @endif
                        @if(!empty($list->utility_bill_2))
                            <a href="{{ config('app.url') }}files/userdocs/{{ $list->utility_bill_2 }}" title="Utility Bill" class="fancybox"><i class="icon-magazine"></i></a>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($list->status == 0) New
                        @elseif($list->status == 1) Processing
                        @elseif($list->status == 2) Canceled
                        @elseif($list->status == 3) Completed
                        @endif
                    </td>
                    <td style="text-align:center;">
                        <a href="{{ url('admin/loan/managers/detail/' . base64_encode($list->user_id)) }}" title="Details"><i class="glyphicon glyphicon-zoom-in"></i></a>
                    </td>
                </tr>
            @empty
                <tr id="set_hide">
                    <td colspan="9" style="text-align:center">No record found!</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<!-- /simple list -->
@if($lists->hasPages())
    <div class="text-center">{{ $lists->appends(request()->query())->links() }}</div>
@endif
<script type="text/javascript">
$(document).ready(function(){
    $(".fancybox").fancybox();
});
</script>
