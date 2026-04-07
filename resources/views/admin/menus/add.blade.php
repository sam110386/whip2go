<?php
/** @var object|null $module */
?>

<div class="panel-body">
    {{-- Legacy JS expects #menuform.serialize() from inputs/selects inside this form --}}
    <form id="menuform">
        <div class="form-group">
            <label style="display:block; font-weight:700;">Name :<span class="text-danger">*</span></label>
            <input
                type="text"
                class="form-control required"
                name="data[AdminModule][module]"
                maxlength="100"
            value="{{ old('data.AdminModule.module', (string) data_get($module, 'module', '')) }}"
            >
        </div>

        <div class="form-group">
            <label style="display:block; font-weight:700;">Url :<span class="text-danger">*</span></label>
            <input
                type="text"
                class="form-control required"
                name="data[AdminModule][module_url]"
            value="{{ old('data.AdminModule.module_url', (string) data_get($module, 'module_url', '')) }}"
            >
        </div>

        <div class="form-group">
            <label style="display:block; font-weight:700;">HTML ID :<span class="text-danger">*</span></label>
            <input
                type="text"
                class="form-control required"
                name="data[AdminModule][html_id]"
            value="{{ old('data.AdminModule.html_id', (string) data_get($module, 'html_id', '')) }}"
            >
            <em>like: index</em>
        </div>

        <div class="form-group">
            <label style="display:block; font-weight:700;">Icon :<span class="text-danger">*</span></label>
            <input
                type="text"
                class="form-control required"
                name="data[AdminModule][icon]"
            value="{{ old('data.AdminModule.icon', (string) data_get($module, 'icon', '')) }}"
            >
            <em>icon class from icon library</em>
        </div>

        <div class="form-group">
            <label style="display:block; font-weight:700;">Parent :<span class="text-danger">*</span></label>
            <select class="form-control" name="data[AdminModule][parent_id]">
                <option value="0">None</option>
                @foreach(($menus ?? []) as $id => $name)
                    <option value="{{ $id }}" @selected((int) data_get($module, 'parent_id', 0) === (int)$id)>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>

        <input
            type="hidden"
            name="data[AdminModule][id]"
            value="{{ data_get($module, 'id', '') }}"
            class="form-control"
        >

        <div class="form-group" style="margin-top:10px;">
            <button type="button" id="savenewMenu" class="btn">Save</button>
        </div>
    </form>
</div>

