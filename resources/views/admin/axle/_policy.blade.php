<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <form class="form-horizontal">
        <legend class="text-semibold">Policy Details</legend>
        <div class="form-group">
            <div class="col-lg-12">
                @if (!empty($axleObj) && ($axleObj['success'] ?? false))
                    <pre>{{ print_r($axleObj['data'], true) }}</pre>
                @else
                    Sorry, something went wrong.
                @endif
            </div>
        </div>
    </form>
</div>
