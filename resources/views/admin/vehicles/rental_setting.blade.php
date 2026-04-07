@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Vehicle Fee Setting')

@section('content')
    <h1>{{ $listTitle ?? 'Vehicle Fee Setting' }}</h1>
    <form method="POST" action="/admin/vehicles/rental_setting/{{ base64_encode((string)($id ?? 0)) }}">
        <fieldset style="margin-bottom:10px;">
            <legend>Vehicle</legend>
            <div>ID: {{ data_get($vehicle, 'vehicle_unique_id', '-') }}</div>
            <input type="hidden" name="DepositRule[vehicle_id]" value="{{ data_get($vehicle, 'id', $id) }}">
            <input type="hidden" name="DepositRule[user_id]" value="{{ data_get($vehicle, 'user_id', 0) }}">
        </fieldset>

        <fieldset style="margin-bottom:10px;">
            <legend>Fees</legend>
            <label>Rate <input type="text" name="Vehicle[rate]" value="{{ data_get($vehicle, 'rate', '') }}"></label>
            <label style="margin-left:8px;">Day Rent <input type="text" name="Vehicle[day_rent]" value="{{ data_get($vehicle, 'day_rent', '') }}"></label>
            <label style="margin-left:8px;">Fare Type <input type="text" name="Vehicle[fare_type]" value="{{ data_get($vehicle, 'fare_type', '') }}"></label>
            <div style="margin-top:8px;">
                <label>Deposit Event <input type="text" name="DepositRule[deposit_event]" value="{{ data_get($depositRule, 'deposit_event', 'P') }}"></label>
                <label style="margin-left:8px;">Deposit Amount <input type="text" name="DepositRule[deposit_amt]" value="{{ data_get($depositRule, 'deposit_amt', 0) }}"></label>
                <label style="margin-left:8px;">Initial Fee <input type="text" name="DepositRule[initial_fee]" value="{{ data_get($depositRule, 'initial_fee', 0) }}"></label>
            </div>
            <div style="margin-top:8px;">
                <label>Tax <input type="text" name="DepositRule[tax]" value="{{ data_get($depositRule, 'tax', 0) }}"></label>
                <label style="margin-left:8px;">Insurance Fee <input type="text" name="DepositRule[insurance_fee]" value="{{ data_get($depositRule, 'insurance_fee', 0) }}"></label>
            </div>
        </fieldset>

        <button type="submit">Save</button>
        <a href="/admin/vehicles/index" style="margin-left:10px;">Return</a>
    </form>
@endsection

