@extends('admin.layouts.app')

@php
    $id ??= '';
    $title ??= 'Add Role';
    $parentRoles ??= collect();
    $mypermissions ??= [];
    $permissions ??= collect();
    $menu ??= collect();
    $selectedMenu ??= [];
    $role ??= collect();
    $menuNameTree = [];

    if (!empty($menu)) {
        $menuNameTree = \App\Helpers\Legacy\NestedTree::getMenuNameTree(0, $menu, $menu, $selectedMenu);
    }

@endphp

@section('title', $title)

@section('content')

    <div class="panel">
        <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;padding: 13px 23px 0;">
            <h3 style="width: 80%; float: left;">{{ $title }}</h3>
        </section>

        <div class="row">
            @includeif('partials.flash')
        </div>

        <div class="row">
            <fieldset class="col-lg-12">
                <form method="POST"
                    action="{{ !empty(data_get($role, 'id', '')) ? url('/admin/roles/add/' . data_get($role, 'id', '')) : url('/admin/roles/add') }}"
                    name="frmadmin" id="frmadmin" class="form-horizontal" onsubmit="return getTreenode();">
                    @csrf

                    <div class="panel-body">
                        <div class="col-lg-6">

                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">
                                    Slug:<span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="AdminRole[slug]"
                                        value="{{ old('AdminRole.slug', data_get($role, 'slug', '')) }}" maxlength="100"
                                        class="form-control required" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">
                                    Name:<span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="AdminRole[name]"
                                        value="{{ old('AdminRole.name', data_get($role, 'name', '')) }}" maxlength="100"
                                        class="form-control required" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">
                                    Role's Parent Role:
                                </label>
                                <div class="col-lg-9">
                                    <select name="AdminRole[parent_id]" class="form-control">
                                        <option value="0">None</option>
                                        @foreach(($parentRoles) as $pid => $pname)
                                            <option value="{{ $pid }}" @selected(old('AdminRole.parent_id', data_get($role, 'parent_id', 0)) == $pid)>
                                                {{ $pname }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-12 control-label text-semibold">
                                    Permission:<span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-12">
                                    <select name="AdminRole[permissions][]" id="AdminRolePermissions"
                                        class="form-control required listbxper" multiple>
                                        @foreach(($permissions) as $id => $pname)
                                            <option value="{{ $id }}" @selected(in_array($id, $mypermissions))>
                                                {{ $pname }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="col-lg-12 control-label text-semibold">
                                    Menu:
                                </label>
                                <div class="col-lg-12">
                                    <div class="dd" id="nestablemenu"></div>
                                    <input type="hidden" name="AdminRole[menu_id]" id="AdminRoleMenuId"
                                        value="{{ implode(',', $selectedMenu) }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label class="col-lg-2 control-label">&nbsp;</label>
                                <div class="col-lg-6">
                                    <button type="submit" class="btn btn-primary">
                                        {{ !empty(data_get($role, 'id', '')) ? 'Update' : 'Save' }}
                                    </button>
                                    <button type="button" class="btn left-margin btn-cancel"
                                        onclick="goBack('/admin/roles/index')">
                                        Return
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="AdminRole[id]" value="{{ data_get($role, 'id', '') }}">
                </form>
            </fieldset>
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
            if ($.fn.bootstrapDualListbox) {
                $('#AdminRolePermissions').bootstrapDualListbox();
            }

            $("#nestablemenu").fancytree({
                checkbox: true,
                selectMode: 3,
                source: @json($menuNameTree),
                select: function (event, data) {
                    var selKeys = $.map(data.tree.getSelectedNodes(), function (node) {
                        return node.key.replace('_', "");
                    });
                    var selRootNodes = data.tree.getSelectedNodes(true);
                    var selRootKeys = $.map(selRootNodes, function (node) {
                        return (node.parent.selected == true) ? node.parent.key.replace('_', "") : '';
                    });
                    const clean = selRootKeys.filter(v => typeof v === "string" ? v.trim() !== "" : v != null);

                    var combined = selKeys.join(",") + ',' + clean.join(",");
                    combined = combined.replace(/^,+|,+$/g, '').replace(/,+/g, ',');
                    $("#AdminRoleMenuId").val(combined);
                }
            });
        });

        function getTreenode() {
            var selects = $("#nestablemenu").fancytree().getSelectedNodes(true);
            alert("ss" + selects);
            alert(JSON.stringify(selects));
            return false;
        }
    </script>
@endpush