@extends('admin.layouts.app')

@section('title', 'Add Card')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">User</span> - Add Card
            </h4>
        </div>
        <div class="heading-elements">
            <button type="submit" form="frmadmin" class="btn btn-primary">Save</button>
            <a href="/cloud/linked_users/ccindex/{{ base64_encode((string)$userId) }}" class="btn btn-default">Return</a>
        </div>
    </div>
</div>

<div class="content">
    @includeif('partials.flash')

    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-flat">
                <div class="panel-heading">
                    <h5 class="panel-title">Card Details</h5>
                </div>

                <div class="panel-body">
                    <form method="POST" action="/cloud/linked_users/ccadd/{{ base64_encode((string)$userId) }}" id="frmadmin" name="frmadmin" class="form-horizontal">
                        @csrf

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Name:<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="text" name="UserCcToken[card_name]" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Card number:<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="text" name="UserCcToken[card_number]" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Expiry month:<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="text" name="UserCcToken[expiry_month]" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Expiry year:<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="text" name="UserCcToken[expiry_year]" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Default:</label>
                            <div class="col-lg-9">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="UserCcToken[is_default]" value="1"> Make default
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-lg-9 col-lg-offset-3">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <a href="/cloud/linked_users/ccindex/{{ base64_encode((string)$userId) }}" class="btn btn-default">Return</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
