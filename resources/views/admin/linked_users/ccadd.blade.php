@extends('admin.layouts.app')

@section('title', 'Add Card')

@section('content')
    <h1>Add card</h1>
    <form method="POST" action="/cloud/linked_users/ccadd/{{ base64_encode((string)$userId) }}">
        @csrf
        <label>Name<br><input type="text" name="UserCcToken[card_name]" required></label><br><br>
        <label>Card number<br><input type="text" name="UserCcToken[card_number]" required></label><br><br>
        <label>Expiry month<br><input type="text" name="UserCcToken[expiry_month]" required></label><br><br>
        <label>Expiry year<br><input type="text" name="UserCcToken[expiry_year]" required></label><br><br>
        <label><input type="checkbox" name="UserCcToken[is_default]" value="1"> Default</label><br><br>
        <button type="submit">Save</button>
        <a href="/cloud/linked_users/ccindex/{{ base64_encode((string)$userId) }}" style="margin-left:10px;">Cancel</a>
    </form>
@endsection

