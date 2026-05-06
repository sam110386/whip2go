<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h5 class="modal-title">Review Images</h5>
</div>

@php
    $initialImages = [];
    if (!empty($result['initial']['CsOrderReview']['CsOrderReviewImage'])) {
        foreach ($result['initial']['CsOrderReview']['CsOrderReviewImage'] as $img) {
            $initialImages[] = legacy_asset('files/reviewimages/' . $img['image']);
        }
    }

    $finalImages = [];
    if (!empty($result['final']['CsOrderReview']['CsOrderReviewImage'])) {
        foreach ($result['final']['CsOrderReview']['CsOrderReviewImage'] as $img) {
            $finalImages[] = legacy_asset('files/reviewimages/' . $img['image']);
        }
    }
@endphp

<div class="modal-body">
    <fieldset class="form-horizontal">
        @if(empty($result))
            <div class="form-group text-center">
                <label class="text-semibold text-danger">Sorry, No review found for this booking.</label>
            </div>
        @endif

        @if(isset($result['initial']))
            <legend class="text-size-mini text-muted text-uppercase no-margin-top">Initial Review Details</legend>
            <div class="form-group no-margin-bottom">
                <label class="col-lg-4 control-label text-semibold">Details:</label>
                <div class="col-lg-8"><p class="form-control-static">{{ $result['initial']['CsOrderReview']['details'] }}</p></div>
            </div>
            <div class="form-group no-margin-bottom">
                <label class="col-lg-4 control-label text-semibold">Beginning Mileage:</label>
                <div class="col-lg-8"><p class="form-control-static">{{ $result['initial']['CsOrderReview']['mileage'] }}</p></div>
            </div>
            @if(!empty($initialImages))
                <div class="form-group">
                    <label class="col-lg-4 control-label text-semibold">Images:</label>
                    <div class="col-lg-8">
                        <button type="button" class="btn btn-link no-padding" onclick="showinitial()">
                            <i class="icon-images2 position-left"></i> View Images
                        </button>
                    </div>
                </div>
            @endif
        @endif

        @if(isset($result['final']))
            <legend class="text-size-mini text-muted text-uppercase margin-top-10">Final Review Details</legend>
            <div class="form-group no-margin-bottom">
                <label class="col-lg-4 control-label text-semibold">Details:</label>
                <div class="col-lg-8"><p class="form-control-static">{{ $result['final']['CsOrderReview']['details'] }}</p></div>
            </div>
            <div class="form-group no-margin-bottom">
                <label class="col-lg-4 control-label text-semibold">Ending Mileage:</label>
                <div class="col-lg-8"><p class="form-control-static">{{ $result['final']['CsOrderReview']['mileage'] }}</p></div>
            </div>
            <div class="form-group no-margin-bottom">
                <label class="col-lg-4 control-label text-semibold">Previous Deposit:</label>
                <div class="col-lg-8"><p class="form-control-static">{{ number_format($result['final']['CsOrderReview']['original_amt'], 2) }}</p></div>
            </div>
            <div class="form-group no-margin-bottom">
                <label class="col-lg-4 control-label text-semibold">Refunded Deposit:</label>
                <div class="col-lg-8"><p class="form-control-static">{{ number_format($result['final']['CsOrderReview']['refund_amt'], 2) }}</p></div>
            </div>
            @if(!empty($finalImages))
                <div class="form-group">
                    <label class="col-lg-4 control-label text-semibold">Images:</label>
                    <div class="col-lg-8">
                        <button type="button" class="btn btn-link no-padding" onclick="showfinal()">
                            <i class="icon-images2 position-left"></i> View Images
                        </button>
                    </div>
                </div>
            @endif
        @endif
    </fieldset>
</div>

<script>
    function showinitial() {
        $.fancybox.open({!! json_encode($initialImages) !!}, {
            type: 'image',
            padding: 0
        });
    }
    function showfinal() {
        $.fancybox.open({!! json_encode($finalImages) !!}, {
            type: 'image',
            padding: 0
        });
    }
</script>
<style>
    .fancybox-overlay {
        z-index: 1000000 !important;
    }
</style>
