<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
    <thead>
        <tr>
            <th>Employer Name</th>
            <th>Employee Name</th>
            <th>Income</th>
            <th>Statement</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($atomics as $atomic)
        @php $a = is_array($atomic) ? (object)$atomic : $atomic; @endphp
        <tr>
            <td id="employer_{{ $a->id }}" class="employer" rel-accountid="{{ $a->id }}">{{ $a->company }}</td>
            <td id="employee_{{ $a->id }}" class="employee" rel-accountid="{{ $a->id }}">{{ $a->company }}</td>
            <td>
                <span class="atomicbalance" rel-accountid="{{ $a->id }}" rel-linkedAccount="{{ $a->linkedAccount }}" rel-userid="{{ $a->user_id }}"></span>
            </td>
            <td>
                <span class="cursor-pointer" onclick="loadatomicstatement('{{ $a->linkedAccount }}',{{ $a->id }})"><i class="icon-file-download"></i></span>
            </td>
        </tr>
        <tr>
            <td id="empstatement_{{ $a->id }}" colspan="4"></td>
        </tr>
        @endforeach
    </tbody>
</table>
