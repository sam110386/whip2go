<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <div class="panel-body fc-scroller">
        <button type="button" class="btn btn-info mt-10 pull-right" onclick="OpenInsurancePayerListUploadPopUp('{{ $recordid }}','statementModal')">Add New</button>
        <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table  table-responsive">
            <thead>
                <tr>
                    <th style="width:5px;">
                        Premium Total
                    </th>
                    <th style="width:10px;">
                        Finance Total
                    </th>
                    <th style="width:5px;">
                        Policy#
                    </th>
                    <th style="width:5px;">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $record)
                    <tr>
                        <td>
                            {{ $record['InsurancePayer']['premium_total'] }}
                        </td>
                        <td>
                            {{ $record['InsurancePayer']['premium_finance_total'] }}
                        </td>
                        <td>
                            {{ $record['InsurancePayer']['policy_number'] }}
                        </td>
                        <td>
                            {{ $record['InsurancePayer']['created'] }}
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
