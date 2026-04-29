@extends('admin.layouts.app')

@section('title', !empty($listTitle) ? $listTitle : 'Add Role')

@section('content')
@php $isEditing = !empty($role) && !empty($role->id); @endphp

<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">Roles</span> - {{ $listTitle ?? ($isEditing ? 'Edit' : 'Add') }}
            </h4>
        </div>
        <div class="heading-elements">
            <div class="heading-btn-group">
                <button type="submit" form="frmadmin" class="btn btn-link btn-float has-text">
                    <i class="icon-database-insert text-primary"></i>
                    <span>{{ $isEditing ? 'Update' : 'Save' }}</span>
                </button>
                <a href="{{ url('/admin/roles/index') }}" class="btn btn-link btn-float has-text">
                    <i class="icon-undo text-primary"></i>
                    <span>Return</span>
                </a>
            </div>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li><a href="{{ url('/admin/roles/index') }}">Roles</a></li>
            <li class="active">{{ $isEditing ? 'Edit' : 'Add' }}</li>
        </ul>
    </div>
</div>

<div class="content">
    <script src="{{ legacy_asset('js/assets/js/plugins/forms/inputs/duallistbox.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/core/libraries/jquery_ui/core.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/core/libraries/jquery_ui/effects.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/core/libraries/jquery_ui/interactions.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/extensions/cookie.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/trees/fancytree_all.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/trees/fancytree_childcounter.js') }}"></script>

    @includeif('partials.flash')

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">{{ $listTitle ?? 'Role' }}</h5>
        </div>

        <div class="panel-body">
            <form method="POST"
                  action="{{ $isEditing ? url('/admin/roles/add/' . $role->id) : url('/admin/roles/add') }}"
                  name="frmadmin" id="frmadmin" class="form-horizontal">
                @csrf

                @if($isEditing)
                    <input type="hidden" name="AdminRole[id]" value="{{ $role->id }}">
                @endif

                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Slug:<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="text" name="AdminRole[slug]" value="{{ old('AdminRole.slug', $role->slug ?? '') }}"
                                    maxlength="100" class="form-control required" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Name:<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="text" name="AdminRole[name]" value="{{ old('AdminRole.name', $role->name ?? '') }}"
                                    maxlength="100" class="form-control required" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Role's Parent Role:</label>
                            <div class="col-lg-9">
                                <select name="AdminRole[parent_id]" class="form-control">
                                    <option value="0">None</option>
                                    @foreach(($parentRoles ?? []) as $pid => $pname)
                                        <option value="{{ $pid }}" @if((string) old('AdminRole.parent_id', $role->parent_id ?? '0') === (string) $pid) selected @endif>
                                            {{ $pname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-12 control-label text-semibold">Permission:<span class="text-danger">*</span></label>
                            <div class="col-lg-12">
                                <select name="AdminRole[permissions][]" id="AdminRolePermissions" class="form-control required listbxper" multiple>
                                    @foreach(($permissions ?? []) as $id => $pname)
                                        <option value="{{ $id }}" @if(in_array((string) $id, array_map('strval', $selectedPermissionIds ?? []), true)) selected @endif>
                                            {{ $pname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="col-lg-12 control-label text-semibold">Menu:</label>
                            <div class="col-lg-12">
                                <div class="dd" id="nestablemenu"></div>
                                <input type="hidden" name="AdminRole[menu_id]" id="AdminRoleMenuId"
                                    value="{{ implode(',', $selectedMenuIds ?? []) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">{{ $isEditing ? 'Update' : 'Save' }} <i class="icon-database-insert position-right"></i></button>
                    <a href="{{ url('/admin/roles/index') }}" class="btn btn-default">Return</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        // Apply dual listbox for permissions
        if ($.fn.bootstrapDualListbox) {
            $('#AdminRolePermissions').bootstrapDualListbox();
        }

        // Hierarchical select for menu selection
        $("#nestablemenu").fancytree({
            checkbox: true,
            selectMode: 3,
            source: @json($fancytreeData ?? []),
            select: function (event, data) {
                // Get a list of all selected nodes, and convert to a key array:
                var selKeys = $.map(data.tree.getSelectedNodes(), function (node) {
                    return node.key.replace('_', "");
                });
                // Get a list of all selected TOP nodes
                var selRootNodes = data.tree.getSelectedNodes(true);
                // ... and convert to a key array:
                var selRootKeys = $.map(selRootNodes, function (node) {
                    return (node.parent.selected == true) ? node.parent.key.replace('_', "") : '';
                });
                const clean = selRootKeys.filter(v => typeof v === "string" ? v.trim() !== "" : v != null);

                var combined = selKeys.join(",") + ',' + clean.join(",");
                // Remove leading/trailing commas and extra commas
                combined = combined.replace(/^,+|,+$/g, '').replace(/,+/g, ',');
                $("#AdminRoleMenuId").val(combined);
            }
        });
    });
</script>
@endsection
