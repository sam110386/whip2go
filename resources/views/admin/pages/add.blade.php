@extends('layouts.admin')

@section('title', $listTitle ?? 'Page')

@section('content')
    <h1>{{ $listTitle ?? 'Page' }}</h1>

    <form method="POST" action="{{ isset($page) && $page ? '/admin/pages/add/' . $page->id : '/admin/pages/add' }}">
        <div style="margin:8px 0;">
            <label>Title* <input type="text" name="Page[title]" value="{{ data_get($page, 'title', '') }}" required></label>
        </div>
        <div style="margin:8px 0;">
            <label>Page Code <input type="text" name="Page[pagecode]" value="{{ data_get($page, 'pagecode', '') }}"></label>
        </div>
        <div style="margin:8px 0;">
            <label>Status
                <select name="Page[status]">
                    <option value="1" @selected((int) data_get($page, 'status', 1) === 1)>Active</option>
                    <option value="0" @selected((int) data_get($page, 'status', 1) === 0)>Inactive</option>
                </select>
            </label>
        </div>
        <div style="margin:8px 0;">
            <label>Meta Title <input type="text" name="Page[meta_title]" value="{{ data_get($page, 'meta_title', '') }}"></label>
        </div>
        <div style="margin:8px 0;">
            <label>Meta Description <input type="text" name="Page[meta_description]" value="{{ data_get($page, 'meta_description', '') }}"></label>
        </div>
        <div style="margin:8px 0;">
            <label>Meta Keyword <input type="text" name="Page[meta_keyword]" value="{{ data_get($page, 'meta_keyword', '') }}"></label>
        </div>
        <div style="margin:8px 0;">
            <label>Description</label>
            <textarea name="Page[description]" rows="10" style="width:100%;">{{ data_get($page, 'description', '') }}</textarea>
        </div>
        <button type="submit">{{ isset($page) && $page ? 'Update' : 'Save' }}</button>
        <a href="/admin/pages/index" style="margin-left:10px;">Return</a>
    </form>
@endsection

