@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Page')

@section('content')
@php $isEditing = isset($page) && $page && !empty(data_get($page, 'id')); @endphp

<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">Pages</span> - {{ $listTitle ?? ($isEditing ? 'Edit' : 'Add') }}
            </h4>
        </div>
        <div class="heading-elements">
            <div class="heading-btn-group">
                <button type="submit" form="frmPage" class="btn btn-link btn-float has-text">
                    <i class="icon-database-insert text-primary"></i>
                    <span>{{ $isEditing ? 'Update' : 'Save' }}</span>
                </button>
                <a href="/admin/pages/index" class="btn btn-link btn-float has-text">
                    <i class="icon-undo text-primary"></i>
                    <span>Return</span>
                </a>
            </div>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li><a href="/admin/pages/index">Pages</a></li>
            <li class="active">{{ $isEditing ? 'Edit' : 'Add' }}</li>
        </ul>
    </div>
</div>

<div class="content">
    @includeif('partials.flash')

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">{{ $listTitle ?? 'Page' }}</h5>
        </div>

        <div class="panel-body">
            <form method="POST"
                  action="{{ $isEditing ? '/admin/pages/add/' . $page->id : '/admin/pages/add' }}"
                  id="frmPage"
                  class="form-horizontal"
                  onsubmit="if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.PageDescription) { CKEDITOR.instances.PageDescription.updateElement(); }">
                @csrf

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Title:<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="text" name="Page[title]" value="{{ data_get($page, 'title', '') }}" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Page Code:</label>
                    <div class="col-lg-9">
                        <input type="text" name="Page[pagecode]" value="{{ data_get($page, 'pagecode', '') }}" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Status:</label>
                    <div class="col-lg-9">
                        <select name="Page[status]" class="form-control">
                            <option value="1" @selected((int) data_get($page, 'status', 1) === 1)>Active</option>
                            <option value="0" @selected((int) data_get($page, 'status', 1) === 0)>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Meta Title:</label>
                    <div class="col-lg-9">
                        <input type="text" name="Page[meta_title]" value="{{ data_get($page, 'meta_title', '') }}" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Meta Description:</label>
                    <div class="col-lg-9">
                        <input type="text" name="Page[meta_description]" value="{{ data_get($page, 'meta_description', '') }}" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Meta Keyword:</label>
                    <div class="col-lg-9">
                        <input type="text" name="Page[meta_keyword]" value="{{ data_get($page, 'meta_keyword', '') }}" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Description:</label>
                    <div class="col-lg-9">
                        <textarea name="Page[description]" id="PageDescription" rows="10" class="form-control" style="width:100%;">{{ data_get($page, 'description', '') }}</textarea>
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">{{ $isEditing ? 'Update' : 'Save' }} <i class="icon-database-insert position-right"></i></button>
                    <a href="/admin/pages/index" class="btn btn-default">Return</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ legacy_asset('js/assets/js/plugins/editors/ckeditor/ckeditor.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof CKEDITOR !== 'undefined' && document.getElementById('PageDescription')) {
        CKEDITOR.replace('PageDescription', { height: 400, width: '100%' });
    }
});
</script>
@endpush
