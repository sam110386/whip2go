@php
    $result ??= [];
    $initialImages = [];
    $finalImages = [];

    foreach (data_get($result, 'initial.cs_order_review_images', []) as $img) {
        $initialImages[] = legacy_asset('files/reviewimages/' . $img['image']);
    }

    foreach (data_get($result, 'final.cs_order_review_images', []) as $img) {
        $finalImages[] = legacy_asset('files/reviewimages/' . $img['image']);
    }

@endphp

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <fieldset class="form-horizontal">
        @if(empty($result))
            <div class="form-group text-center">
                <label class="text-semibold text-danger">Sorry, No review found for this booking.</label>
            </div>
        @endif

        @if(isset($result['initial']))
            <legend class="text-size-mini text-muted text-uppercase no-margin-top">
                Initial Review Details
            </legend>

            <div class="form-group no-margin-bottom">
                <label class="col-lg-4 control-label text-semibold">
                    Details:
                </label>
                <div class="col-lg-8">
                    <p class="form-control-static">
                        {{ data_get($result, 'initial.details', '') }}
                    </p>
                </div>
            </div>
            <div class="form-group no-margin-bottom">
                <label class="col-lg-4 control-label text-semibold">
                    Beginning Mileage:
                </label>
                <div class="col-lg-8">
                    <p class="form-control-static">
                        {{ data_get($result, 'initial.mileage', '') }}
                    </p>
                </div>
            </div>
            @if(!empty($initialImages))
                <div class="form-group">
                    <label class="col-lg-4 control-label text-semibold">
                        Images:
                    </label>
                    <div class="col-lg-8">
                        <button type="button" class="btn btn-link no-padding" onclick="showinitial()">
                            <i class="icon-images2 position-left"></i> View Images
                        </button>
                    </div>
                </div>
            @endif
        @endif

        @if(isset($result['final']))
            <legend class="text-size-mini text-muted text-uppercase margin-top-10">
                Final Review Details
            </legend>

            <div class="form-group no-margin-bottom">
                <label class="col-lg-4 control-label text-semibold">
                    Details:
                </label>
                <div class="col-lg-8">
                    <p class="form-control-static">
                        {{ data_get($result, 'final.details', '') }}
                    </p>
                </div>
            </div>
            <div class="form-group no-margin-bottom">
                <label class="col-lg-4 control-label text-semibold">
                    Ending Mileage:
                </label>
                <div class="col-lg-8">
                    <p class="form-control-static">
                        {{ data_get($result, 'final.mileage', '') }}
                    </p>
                </div>
            </div>
            <div class="form-group no-margin-bottom">
                <label class="col-lg-4 control-label text-semibold">
                    Previous Deposit:
                </label>
                <div class="col-lg-8">
                    <p class="form-control-static">
                        {{ number_format(data_get($result, 'final.original_amt'), 2) }}
                    </p>
                </div>
            </div>
            <div class="form-group no-margin-bottom">
                <label class="col-lg-4 control-label text-semibold">
                    Refunded Deposit:
                </label>
                <div class="col-lg-8">
                    <p class="form-control-static">
                        {{ number_format(data_get($result, 'final.refund_amt'), 2) }}
                    </p>
                </div>
            </div>
            @if(!empty($finalImages))
                <div class="form-group">
                    <label class="col-lg-4 control-label text-semibold">
                        Images:
                    </label>
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
            'type': 'image',
            'transitionIn': 'none',
            'transitionOut': 'none',
            'titlePosition': 'over'
        });
    }
    function showfinal() {
        $.fancybox.open({!! json_encode($finalImages) !!}, {
            'type': 'image',
            'transitionIn': 'none',
            'transitionOut': 'none',
            'titlePosition': 'over'
        });
    }
</script>

<style>
    .fancybox-overlay {
        z-index: 1000000 !important;
    }
</style>