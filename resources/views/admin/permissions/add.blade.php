@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Permission')

@push('head_scripts')
    <script src="{{ legacy_asset('js/assets/js/plugins/forms/inputs/duallistbox.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/core/libraries/jquery_ui/core.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/core/libraries/jquery_ui/effects.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/core/libraries/jquery_ui/interactions.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/extensions/cookie.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/trees/fancytree_all.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/trees/fancytree_childcounter.js') }}"></script>
@endpush

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">{{ $listTitle }}</span></h4>
            </div>
        </div>
    </div>

    <div class="row">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    </div>

    <div class="panel">
        <div class="panel-body">
            <form method="POST"
                action="{{ isset($permission) && $permission ? '/admin/permissions/add/' . $permission->id : '/admin/permissions/add' }}"
                class="form-horizontal" id="frmadmin">
                @csrf
                <div class="form-group">
                    <label class="col-lg-2 control-label">Name :<span class="text-danger">*</span></label>
                    <div class="col-lg-4">
                        <input type="text" name="AdminPermission[name]" class="form-control required" maxlength="100"
                            value="{{ data_get($permission, 'name', '') }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">Permissions:</label>
                    <div class="col-lg-4">
                        <select name="AdminPermission[type]" id="AdminPermissionType" class="form-control required">
                            <option value="all" @selected((data_get($permission, 'type', 'all') === 'all'))>All</option>
                            <option value="custom" @selected((data_get($permission, 'type', 'all') !== 'all'))>Custom
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
                        <button type="submit"
                            class="btn btn-primary">{{ isset($permission) && $permission ? 'Update' : 'Save' }}</button>
                        <a href="/admin/permissions/index" class="btn btn-default" style="margin-left:10px;">Return</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        $(function () {
            var treeData = @json($treeData ?? []);

            $("#nestablemenu").fancytree({
                checkbox: true,
                selectMode: 3,
                source: treeData,
                select: function (event, data) {
                    var arr = {};
                    $.map(data.tree.getSelectedNodes(), function (node) {
                        if (node.data.parent_module) {
                            if (arr[node.data.parent_module]) {
                                arr[node.data.parent_module].push(node.key);
                            } else {
                                arr[node.data.parent_module] = [node.key];
                            }
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
                    $("#AdminPermissionWrapper").removeClass('hide');
                }
            });
        });
    </script>
@endpush