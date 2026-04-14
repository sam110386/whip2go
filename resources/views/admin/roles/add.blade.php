@extends('layouts.admin')

@section('title', !empty($listTitle) ? $listTitle : 'Add Role')

@section('content')
    <h1>{{ !empty($listTitle) ? $listTitle : 'Role' }}</h1>

    @if(!empty($error))
        <div style="color:#b00020; margin: 8px 0;">
            {{ $error }}
        </div>
    @endif

    @php $isEditing = !empty($role) && !empty($role->id); @endphp

    <form method="POST" action="{{ $isEditing ? ('/admin/roles/add/' . $role->id) : '/admin/roles/add' }}" style="display:flex; flex-direction:column; gap:12px; max-width: 720px;">
        @csrf

        @if($isEditing)
            <input type="hidden" name="AdminRole[id]" value="{{ $role->id }}">
        @endif

        <label>
            Slug*
            <input type="text" name="AdminRole[slug]" value="{{ $role->slug ?? '' }}" required>
        </label>

        <label>
            Name*
            <input type="text" name="AdminRole[name]" value="{{ $role->name ?? '' }}" required>
        </label>

        <label>
            Parent Role
            <select name="AdminRole[parent_id]">
                <option value="0">None</option>
                @foreach(($parentRoles ?? []) as $pid => $pname)
                    <option value="{{ $pid }}" @if(!empty($role) && (string)($role->parent_id ?? '0') === (string)$pid) selected @endif>
                        {{ $pname }}
                    </option>
                @endforeach
            </select>
        </label>

        <div>
            <div style="margin-bottom:6px; font-weight:700;">Permissions*</div>
            <select name="AdminRole[permissions][]" multiple size="8" style="width:100%;">
                @foreach(($permissions ?? []) as $id => $pname)
                    <option value="{{ $id }}"
                        @if(!empty($selectedPermissionIds) && is_array($selectedPermissionIds) && in_array((string)$id, array_map('strval', $selectedPermissionIds), true)) selected @endif>
                        {{ $pname }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <div style="margin-bottom:6px; font-weight:700;">Menu</div>
            <select name="AdminRole[menu_id][]" multiple size="8" style="width:100%;">
                @foreach(($menus ?? []) as $m)
                    <option value="{{ $m->id }}"
                        @if(!empty($selectedMenuIds) && is_array($selectedMenuIds) && in_array((string)$m->id, array_map('strval', $selectedMenuIds), true)) selected @endif>
                        {{ $m->module ?? ('Menu #' . $m->id) }}
                        @if(!empty($m->parent_id) && (int)$m->parent_id !== 0)
                            (sub)
                        @endif
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display:flex; gap:10px; align-items:center;">
            <button type="submit">{{ $isEditing ? 'Update' : 'Save' }}</button>
            <a href="/admin/roles/index">Cancel</a>
        </div>
    </form>
@endsection

