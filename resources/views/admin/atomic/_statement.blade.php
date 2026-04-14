@foreach ($statements as $statement)
<fieldset class="col-md-12">
    <div class="panel panel-flat panel-collapsed">
        <div class="panel-heading">
            <h6 class="panel-title">
                {{ date('m/d/Y', strtotime($statement['date'])) }} : Net Amount :${{ $statement['netAmount'] }}
            </h6>
            <div class="heading-elements">
                <ul class="icons-list"><li><a data-action="collapse" class="rotate-180"></a></li></ul>
            </div>
            <a class="heading-elements-toggle"><i class="icon-menu"></i></a>
        </div>
        <div class="panel-body" style="display: none;">
            <legend class="text-semibold">Earnings :</legend>
            <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                @foreach ($statement['earnings'] as $earning)
                <tr><td>{{ $earning['rawLabel'] }}</td><td>{{ $earning['amount'] }}</td></tr>
                @endforeach
            </table>
            <legend class="text-semibold">Deductions :</legend>
            <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                @foreach ($statement['deductions'] as $deduction)
                <tr><td>{{ $deduction['rawLabel'] }}</td><td>{{ $deduction['amount'] }}</td></tr>
                @endforeach
            </table>
        </div>
    </div>
</fieldset>
@endforeach
