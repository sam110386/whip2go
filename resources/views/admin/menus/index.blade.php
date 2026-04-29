@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Manage Menus')

@php
    $listTitle ??= 'Menu Manager';
    $menu ??= [];
    $menus ??= [];
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage</span>
                    Menus
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-heading">
            <h5 class="panel-title">{{ $listTitle }}</h5>
        </div>
        <div class="panel-body">
            <div class="row">
                <fieldset class="col-lg-6">
                    <div class="panel-body">
                        <button class="btn btn-primary" id="nestablesave" type="button">Save Menu</button>
                        <button class="btn btn-primary" id="nestablerefresh" type="button">Refresh</button>
                        <div class="dd" id="nestablemenu">
                            @include('admin.menus._menu_tree', ['nodes' => $menu])
                        </div>
                    </div>
                </fieldset>

                <fieldset class="col-lg-6" id="menuwrapper">
                    @include('admin.menus.add', ['module' => null, 'menus' => $menus])
                </fieldset>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style type="text/css">
        .dd { position: relative; display: block; margin: 0; padding: 0; max-width: 600px; list-style: none; font-size: 13px; line-height: 20px; }
        .dd-list { display: block; position: relative; margin: 0; padding: 0; list-style: none; }
        .dd-list .dd-list { padding-left: 30px; }
        .dd-item, .dd-empty, .dd-placeholder { display: block; position: relative; margin: 0; padding: 0; min-height: 20px; font-size: 13px; line-height: 20px; }
        .dd-handle { display: block; height: 30px; margin: 5px 0; padding: 5px 10px; color: #333; text-decoration: none; font-weight: bold; border: 1px solid #ccc; background: #fafafa; border-radius: 3px; box-sizing: border-box; }
        .dd-handle:hover { color: #2ea8e5; background: #fff; }
        .dd-item > button { display: block; position: relative; cursor: pointer; float: left; width: 25px; height: 20px; margin: 5px 0; padding: 0; text-indent: 100%; white-space: nowrap; overflow: hidden; border: 0; background: transparent; font-size: 12px; line-height: 1; text-align: center; font-weight: bold; }
    </style>
@endpush

@push('scripts')
    <script src="{{ legacy_asset('js/assets/js/plugins/extensions/jquery.nestable.js') }}"></script>
    <script type="text/javascript">
        jQuery(function () {
            if (jQuery.fn.nestable) {
                $('#nestablemenu').nestable();
            }

            jQuery(document).on('click', 'a.tree_branch_delete', function () {
                var id = jQuery(this).data('id');
                if (!confirm('Are you sure? You will not be able to recover this menu!')) {
                    return;
                }
                jQuery.ajax({
                    method: 'post',
                    url: '/admin/menus/delete/' + id,
                    data: {_method: 'delete'},
                    success: function () {
                        jQuery('#nestablemenu').load('/admin/menus/reload', function () {
                            if (jQuery.fn.nestable) {
                                jQuery('#nestablemenu').nestable();
                            }
                        });
                    }
                });
            });

            jQuery(document).on('click', 'a.tree_branch_edit', function () {
                var id = jQuery(this).data('id');
                jQuery('#menuwrapper').load('/admin/menus/edit/' + id);
            });

            jQuery('#nestablesave').click(function () {
                var serialize = jQuery('#nestablemenu').nestable('serialize');
                jQuery.post('/admin/menus/updateOrder', {
                    _order: JSON.stringify(serialize)
                }, function () {
                    jQuery('#nestablemenu').load('/admin/menus/reload', function () {
                        if (jQuery.fn.nestable) {
                            jQuery('#nestablemenu').nestable();
                        }
                    });
                });
            });

            jQuery('#nestablerefresh').click(function () {
                jQuery('#nestablemenu').load('/admin/menus/reload', function () {
                    if (jQuery.fn.nestable) {
                        jQuery('#nestablemenu').nestable();
                    }
                });
            });

            jQuery(document).on('click', '#savenewMenu', function () {
                var serialize = jQuery('#menuform').serialize();
                jQuery.post('/admin/menus/saveNewMenu', serialize, function (data) {
                    if (data && data.status) {
                        jQuery('#menuform .form-control').val('');
                        jQuery('#nestablemenu').load('/admin/menus/reload', function () {
                            if (jQuery.fn.nestable) {
                                jQuery('#nestablemenu').nestable();
                            }
                        });
                    } else {
                        alert((data && data.message) ? data.message : 'Failed to save menu');
                    }
                }, 'json');
            });
        });
    </script>
@endpush
