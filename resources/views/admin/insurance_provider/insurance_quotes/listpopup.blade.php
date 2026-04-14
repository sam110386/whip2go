<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">

    <div class="panel-body">
        <div class="heading-elements">
            <a href="javascript:;" onclick="OpenBoyiByDIAPopUp('{{ $bookingid }}')" class="btn btn-success">New</a>
        </div>
        <legend class="text-size-large text-bold">BYOI By DIA:</legend>
        
        <table width="100%" cellpadding="2" cellspacing="1"  border="0"  class="table table-responsive">
            <thead>
                <tr>
                    <td>Provider</td>
                    <td>Total Amount</td>
                    <td>Daily Rate</td>
                    <td>Limit</td>
                    <td>Action</td>
                </tr>
            </thead>
            <tbody>
                @foreach($quotes as $quote)
                <tr>
                    <td>{{ $quote['InsuranceProvider']['name'] }}</td>
                    <td>{{ $quote['InsuranceQuote']['quote_amount'] }}</td>
                    <td>{{ $quote['InsuranceQuote']['daily_rate'] }}</td>
                    <td>{{ $quote['InsuranceQuote']['total_limit'] }}</td>
                    <td>
                        <a href="javascript:;" onclick="OpenBoyiByDIAPopUp('{{ $bookingid }}','{{ $quote['InsuranceQuote']['id'] }}')"><i class="icon-pencil"></i></a>
                        <a href="javascript:;" onclick="DeleteBoyiByDIAPopUp('{{ $bookingid }}','{{ $quote['InsuranceQuote']['id'] }}')"><i class="icon-trash"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>
