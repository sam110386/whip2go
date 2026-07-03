@extends('admin.layouts.app')

@php
    $id ??= '';
    $title ??= 'Add Permission';
    $selectedMenu ??= '';
    $actions ??= [];
    $permission ??= collect();
    $menuNameTree = [];

    if (!empty($actions)) {
        $menuNameTree = \App\Helpers\Legacy\PermissionNestedTree::getMenuNameTree($actions, json_decode($selectedMenu, 1));
    }

@endphp

@section('title', $title)

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">{{ $title }}</span>
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form method="POST"
                action="{{ url('admin/permissions/add' . (!empty(data_get($permission, 'id')) ? '/' . data_get($permission, 'id') : '')) }}"
                class="form-horizontal" id="frmadmin">

                @csrf

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Name :<span class="text-danger">*</span></label>
                    <div class="col-lg-4">
                        <input type="text" name="AdminPermission[name]" class="form-control required" maxlength="100"
                            value="{{ data_get($permission, 'name', '') }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Permissions:
                    </label>
                    <div class="col-lg-4">
                        <select name="AdminPermission[type]" id="AdminPermissionType" class="form-control required">
                            <option value="all" @selected((data_get($permission, 'type', 'all') === 'all'))>
                                All
                            </option>
                            <option value="custom" @selected((data_get($permission, 'type', 'all') !== 'all'))>
                                Custom
                            </option>
                        </select>
                    </div>
                </div>

                <div id="AdminPermissionWrapper"
                    class="form-group {{ (data_get($permission, 'type', 'all') === 'all') ? 'hide' : '' }}">
                    <label class="col-lg-2 control-label"></label>
                    <div class="col-lg-10">
                        <div class="dd" id="nestablemenu"></div>
                        <input type="hidden" name="AdminPermission[permissions]" id="AdminPermissionPermissions"
                            value="{{ data_get($permission, 'permissions', '') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">&nbsp;</label>
                    <div class="col-lg-10">
                        <button type="submit" class="btn btn-primary">
                            {{ !empty(data_get($permission, 'id')) ? 'Update' : 'Save' }}
                        </button>
                        <button type="button" class="btn left-margin btn-cancel"
                            onclick="goBack('/admin/permissions/index')">
                            Return
                        </button>
                    </div>
                </div>

                <input type="hidden" name="AdminPermission[id]" value="{{ data_get($permission, 'id', '') }}">
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/assets/js/plugins/forms/inputs/duallistbox.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/core/libraries/jquery_ui/core.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/core/libraries/jquery_ui/effects.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/core/libraries/jquery_ui/interactions.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/extensions/cookie.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/trees/fancytree_all.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/trees/fancytree_childcounter.js') }}"></script>

    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("#frmadmin").validate();
        });

        $(function () {
            $('#AdminPermissionPermissions').bootstrapDualListbox();

            $("#nestablemenu").fancytree({
                checkbox: true,
                selectMode: 3,
                source: @json($menuNameTree),
                select: function (event, data) {
                    var arr = {};
                    $.map(data.tree.getSelectedNodes(), function (node) {

                        if (arr[node.data.parent_module]) {
                            arr[node.data.parent_module] = [...arr[node.data.parent_module], node.key];
                        } else {
                            arr[node.data.parent_module] = [node.key];
                        }
                    });
                    $('#AdminPermissionPermissions').val(JSON.stringify(arr));
                }
            });

            $("#AdminPermissionType").change(function () {
                if ($(this).val() == 'all') {
                    $('#AdminPermissionPermissions').val('*');
                    $("#AdminPermissionWrapper").addClass('hide');
                } else {
                    $('#AdminPermissionPermissions').val('{{ $selectedMenu }}');
                    $("#AdminPermissionWrapper").removeClass('hide');
                }
            });

        });

    </script>

@endpush