@if(!empty($notification))
    <legend class="text-size-medium text-samibold text-center">
        <em class="text-danger">{{ $notification }}</em>
    </legend>
@endif

<form action="{{url('create')}}" method="POST" name="triplogForm" id="triplogForm" class="form-horizontal">
    <div class="masonry">

    </div>
</form>


<script type="text/javascript">
    jQuery(document).ready(function () {
        $('.timeClass').timepicki();
        $("#triplogForm").validate();
    });
</script>