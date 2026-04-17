<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">

    <div class="panel-body">
        <div class="row">
        {{ config('app.url') }}/{{ $file }}
            @if(empty($file))
                <div class="col-lg-12">
                    Sorry, Signed file couldnt be downloaded.
                </div>
            @else
            <div class="col-lg-12">
                <iframe src="{{ config('app.url') }}/{{ $file }}" width="100%" style="height:900px;"></iframe>
            </div>
            @endif
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>
