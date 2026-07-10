<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">

    <div class="panel-body">
        <div class="heading-elements">
            <a href="javascript:void(0)" onclick="OpenBoyiByDIAPopUp('{{ $bookingid }}')"
                class="btn btn-success">New</a>
        </div>
        <legend class="text-size-large text-bold">BYOI By DIA:</legend>

        <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
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
                        <td>
                            {{ data_get($quote, 'provider.name', '') }}
                        </td>
                        <td>
                            {{ data_get($quote, 'quote_amount', '') }}
                        </td>
                        <td>
                            {{ data_get($quote, 'daily_rate', '') }}
                        </td>
                        <td>
                            {{ data_get($quote, 'total_limit', '') }}
                        </td>
                        <td>
                            <a href="javascript:void(0)"
                                onclick="OpenBoyiByDIAPopUp('{{ $bookingid }}','{{ data_get($quote, 'id') }}')">
                                <i class="icon-pencil"></i>
                            </a>
                            <a href="javascript:void(0)"
                                onclick="DeleteBoyiByDIAPopUp('{{ $bookingid }}','{{ data_get($quote, 'id') }}')">
                                <i class="icon-trash"></i>
                            </a>
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