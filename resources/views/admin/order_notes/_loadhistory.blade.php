<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Booking</span> - Notes</h4>
        </div>
        <div class="heading-elements">
            <button type="button" class="btn btn-primary" title="Create New Note" onclick="return loadNewBookingNotesPopup('{{ $orderid }}','{{ $parentid }}');">New</button>
        </div>
    </div>
</div>
<div class="panel">
    <div class="panel-body" id="ordernotelisting">
        @include('admin.order_notes._history')
    </div>
</div>
