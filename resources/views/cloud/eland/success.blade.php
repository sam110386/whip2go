@extends('layouts.without_header_footer', ['title' => 'Application Submitted'])
@section('content')
<script type="text/javascript">$(function () { $('.btnClose').click(function () { close(); }); });</script>
<div class="panel panel-white">
    <fieldset>
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group text-center">
                    <h2>Your application submitted successfully.</h2>
                </div>
            </div>
        </div>
    </fieldset>
</div>
@endsection
