<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <form action="#" method="POST" name="frmadmin" class="form-horizontal">
        @csrf

        <div class="panel-body">
            <legend class="text-size-large text-bold">Please choose any:</legend>

            <div class="form-group">
                <div class="col-lg-4">
                    <button type="button" class="btn btn-primary pl-3 pr-3"
                        onclick="vehicleSellingOptionAgreeToSellPopup('plaidModal', '{{ data_get($booking, 'orderDepositRule.id') }}')">
                        Agree to Sell <i class="icon-arrow-right14 position-right"></i>
                    </button>
                </div>
                <div class="col-lg-4">
                    <button type="button" class="btn btn-primary pl-3 pr-3"
                        onclick="vehicleSellingOptionNotInterested('{{ data_get($booking, 'orderDepositRule.id') }}')">
                        Not Interested <i class="icon-arrow-right14 position-right"></i>
                    </button>
                </div>
                @if(!$is_admin)
                    <div class="col-lg-4">
                        <button type="button" class="btn btn-primary pl-3 pr-3"
                            onclick="vehicleSellingOptionFindReplacementPopup('plaidModal', '{{ data_get($booking, 'orderDepositRule.id') }}')">
                            Find A Replacement <i class="icon-arrow-right14 position-right"></i>
                        </button>
                    </div>
                @endif
            </div>

            @if(!empty($free_two_move) && !empty($free_two_move['reference']))
                <div class="form-group mt-10">
                    <div class="col-lg-4">
                        <button type="button" class="btn btn-primary pl-3 pr-3"
                            onclick="vehicleFree2moveAgreement('plaidModal', '{{ $free_two_move['reference'] }}')">
                            Free2move Agreement <i class="icon-arrow-right14 position-right"></i>
                        </button>
                    </div>
                </div>
            @endif

            @if($is_admin)
                @if(!empty($selling_option['vehicle_replacement']))
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Vehicle Replacement Choice :</label>
                        <div class="col-lg-8 control-label text-bold">
                            {{ $selling_option['vehicle_replacement'] }}
                        </div>
                    </div>
                @endif
            @endif
        </div>

        <input type="hidden" name="OrderDepositRule[id]" value="{{ data_get($booking, 'orderDepositRule.id') }}">
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>