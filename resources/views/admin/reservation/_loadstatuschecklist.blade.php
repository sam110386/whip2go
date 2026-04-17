<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<style>.form-horizontal .editable { padding-top: 0px; }</style>
<div class="modal-body">
    <form action="#" method="POST" class="form-horizontal">
        @csrf
        <div class="panel-body">
            <legend class="text-size-large text-bold">Status Checklist:</legend>
            @php $i = 1; @endphp
            @foreach($checklist as $key => $check)
            <div class="form-group">
                <label class="col-lg-9 control-label"><strong>{{ $i++ }} : </strong>{{ $check }}</label>
                <div class="col-lg-1 control-label">
                    <a href="#" class="editable" data-type="select" data-inputclass="form-control" data-pk="{{ $orderid }}" data-value="{{ $bookingchecks[$key.'_value'] ?? '--' }}" data-title="Select .." id="{{ $key }}_value" data-url="{{ $dataUrl }}">{{ isset($bookingchecks[$key.'_value']) && $bookingchecks[$key.'_value'] ? $bookingchecks[$key.'_value'] : '--' }}</a>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-1 control-label text-bold text-info"><em>Note:</em></label>
                <div class="col-lg-9 control-label">
                    <a href="#" class="editablenote" data-type="textarea" data-inputclass="form-control" data-pk="{{ $orderid }}" data-value="{{ $bookingchecks[$key.'_note'] ?? 'Empty' }}" data-title="Empty .." id="{{ $key }}_note" data-url="{{ $dataUrl }}">{{ $bookingchecks[$key.'_note'] ?? 'Empty' }}</a>
                </div>
            </div>
            @endforeach
        </div>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>
