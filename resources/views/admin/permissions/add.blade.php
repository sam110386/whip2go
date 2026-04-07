@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Permission')

@section('content')
    <h1>{{ $listTitle ?? 'Permission' }}</h1>

    <form method="POST" action="{{ isset($permission) && $permission ? '/admin/permissions/add/' . $permission->id : '/admin/permissions/add' }}">
        {{-- CSRF middleware is disabled for legacy admin routes, but keep markup simple --}}

        <div style="margin: 10px 0;">
            <label>
                Name*
                <input type="text" name="AdminPermission[name]" value="{{ data_get($permission, 'name', '') }}" required>
            </label>
        </div>

        <div style="margin: 10px 0;">
            <label>
                Permissions type
                <select name="AdminPermission[type]">
                    <option value="all" @selected((data_get($permission, 'type', 'all') === 'all'))>All</option>
                    <option value="custom" @selected((data_get($permission, 'type', 'all') !== 'all'))>Custom</option>
                </select>
            </label>
        </div>

        <div style="margin: 10px 0;">
            <label style="display:block;">
                Permissions (store '*' for all / JSON or text for custom)
                <textarea name="AdminPermission[permissions]" rows="6" style="width:100%;"
                          placeholder='* or JSON/text'>{{ data_get($permission, 'permissions', '') }}</textarea>
            </label>
        </div>

        <div style="margin: 10px 0;">
            <button type="submit">{{ isset($permission) && $permission ? 'Update' : 'Save' }}</button>
            <a href="/admin/permissions/index" style="margin-left:10px;">Return</a>
        </div>
    </form>
@endsection

