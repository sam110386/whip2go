@extends('admin.layouts.app')

@section('title', !empty($listTitle) ? $listTitle : 'Add Role')

@section('content')
    <div class="panel">
        <script src="{{ legacy_asset('js/assets/js/plugins/forms/inputs/duallistbox.min.js') }}"></script>
        <script src="{{ legacy_asset('js/assets/js/core/libraries/jquery_ui/core.min.js') }}"></script>
        <script src="{{ legacy_asset('js/assets/js/core/libraries/jquery_ui/effects.min.js') }}"></script>
        <script src="{{ legacy_asset('js/assets/js/core/libraries/jquery_ui/interactions.min.js') }}"></script>
        <script src="{{ legacy_asset('js/assets/js/plugins/extensions/cookie.js') }}"></script>
        <script src="{{ legacy_asset('js/assets/js/plugins/trees/fancytree_all.min.js') }}"></script>
        <script src="{{ legacy_asset('js/assets/js/plugins/trees/fancytree_childcounter.js') }}"></script>

        <!--heading starts-->
        <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;padding: 13px 23px 0;">
            <h3 style="width: 80%; float: left;">{{ $listTitle }}</h3>
        </section>

        @include('partials.flash')

        <div class="row">
            <fieldset class="col-lg-12">
                @php $isEditing = !empty($role) && !empty($role->id); @endphp

                <form method="POST" action="{{ $isEditing ? url('/admin/roles/add/' . $role->id) : url('/admin/roles/add') }}"
                    name="frmadmin" id="frmadmin" class="form-horizontal">
                    @csrf

                    @if($isEditing)
                        <input type="hidden" name="AdminRole[id]" value="{{ $role->id }}">
                    @endif

                    <div class="panel-body">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="col-lg-3 control-label">Slug :<span class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <input type="text" name="AdminRole[slug]" value="{{ old('AdminRole.slug', $role->slug ?? '') }}"
                                        maxlength="100" class="form-control required" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-3 control-label">Name :<span class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <input type="text" name="AdminRole[name]" value="{{ old('AdminRole.name', $role->name ?? '') }}"
                                        maxlength="100" class="form-control required" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-3 control-label">Role's Parent Role :</label>
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
                                <label class="col-lg-12 control-label">Permission:<span class="text-danger">*</span></label>
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
                                <label class="col-lg-12 control-label">Menu:</label>
                                <div class="col-lg-12">
                                    <div class="dd" id="nestablemenu"></div>
                                    <input type="hidden" name="AdminRole[menu_id]" id="AdminRoleMenuId"
                                        value="{{ implode(',', $selectedMenuIds ?? []) }}">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="form-group">
                                <label class="col-lg-2 control-label">&nbsp;</label>
                                <div class="col-lg-6">
                                    <button type="submit" class="btn">{{ $isEditing ? 'Update' : 'Save' }}</button>
                                    <button type="button" class="btn left-margin btn-cancel" onClick="window.location.href='{{ url('/admin/roles/index') }}'">Return</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </fieldset>
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