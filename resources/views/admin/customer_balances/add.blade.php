@extends('admin.layouts.app')

@section('title', $listTitle)

@section('content')
    <h1>{{ $listTitle }}</h1>

    @if (session('success'))
        <p style="color:#0a0;">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p style="color:#b00020;">{{ session('error') }}</p>
    @endif

    @php
        $b = $balance;
    @endphp

    <form method="post" action="/admin/customer_balances/add{{ $b ? '/' . base64_encode((string)$b->id) : '' }}" style="max-width:720px;">
        @if ($b)
            <input type="hidden" name="CsUserBalance[id]" value="{{ $b->id }}">
        @endif

        <div style="margin-bottom:14px;">
            <label><strong>Driver (user id)</strong></label><br>
            @if ($b)
                <input type="hidden" name="CsUserBalance[user_id]" value="{{ $b->user_id }}">
                <span>{{ $b->user_id }}</span>
            @else
                <input type="number" name="CsUserBalance[user_id]" value="{{ old('CsUserBalance.user_id') }}" min="1" required style="width:200px;">
            @endif
        </div>

        <div style="margin-bottom:14px;">
            <label>Status</label><br>
            <select name="CsUserBalance[status]">
                <option value="1" @selected((int)old('CsUserBalance.status', $b->status ?? 1) === 1)>Active</option>
                <option value="0" @selected((int)old('CsUserBalance.status', $b->status ?? 1) === 0)>Inactive</option>
                <option value="2" @selected((int)old('CsUserBalance.status', $b->status ?? 1) === 2)>Completed</option>
            </select>
        </div>

        @if ($b)
            <p><strong>Current</strong> — Charge on driver: {{ $b->credit }} &nbsp; Debit: {{ $b->debit }} &nbsp; Balance: {{ $b->balance }}</p>
        @endif

        <fieldset style="border:1px solid #ddd; padding:12px; margin-bottom:14px;">
            <legend>Update Balance</legend>
            <div style="margin-bottom:10px;">
                <label>Credit / Debit</label><br>
                <select name="CsUserBalance[creditdebit]">
                    <option value="credit">Charge To Driver</option>
                    <option value="debit">Give Refund to Customer</option>
                </select>
                <div style="font-size:12px;color:#555;">Credit: charge to driver. Debit: refund to customer.</div>
            </div>
            <div style="margin-bottom:10px;">
                <label>Type</label><br>
                <select name="CsUserBalance[type]">
                    @foreach ($balanceTypes as $k => $label)
                        <option value="{{ $k }}" @selected((string)old('CsUserBalance.type', $b->type ?? '') === (string)$k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <label>Amount</label><br>
                <input type="text" name="CsUserBalance[balance]" value="{{ old('CsUserBalance.balance') }}" class="form-control" style="width:200px;">
            </div>
        </fieldset>

        <fieldset style="border:1px solid #ddd; padding:12px; margin-bottom:14px;">
            <legend>Balance capture</legend>
            <div style="margin-bottom:10px;">
                <label>Capture as</label><br>
                <select name="CsUserBalance[chargetype]" id="CsUserBalanceChargetype">
                    <option value="lumpsum" @selected(old('CsUserBalance.chargetype', $b->chargetype ?? 'lumpsum') === 'lumpsum')>Lumpsum</option>
                    <option value="installment" @selected(old('CsUserBalance.chargetype', $b->chargetype ?? '') === 'installment')>Installment</option>
                </select>
            </div>
            <div class="installment-row" style="margin-bottom:10px; display:none;">
                <label>Installment type</label><br>
                <select name="CsUserBalance[installment_type]">
                    <option value="daily" @selected(old('CsUserBalance.installment_type', $b->installment_type ?? 'daily') === 'daily')>Daily</option>
                    <option value="weekly" @selected(old('CsUserBalance.installment_type', $b->installment_type ?? '') === 'weekly')>Weekly</option>
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <label>Week day</label><br>
                <select name="CsUserBalance[installment_day]">
                    @foreach ($weekdays as $k => $label)
                        <option value="{{ $k }}" @selected(old('CsUserBalance.installment_day', $b->installment_day ?? 'sun') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="installment-row" style="margin-bottom:10px; display:none;">
                <label>Installment</label><br>
                <input type="text" name="CsUserBalance[installment]" value="{{ old('CsUserBalance.installment', $b->installment ?? '0') }}" style="width:200px;">
            </div>
        </fieldset>

        <div style="margin-bottom:14px;">
            <label>Note</label><br>
            <input type="text" name="CsUserBalance[note]" value="{{ old('CsUserBalance.note', $b->note ?? '') }}" maxlength="255" style="width:100%; max-width:520px;">
        </div>

        <button type="submit" style="padding:8px 16px;">Save</button>
        <a href="/admin/customer_balances/index" style="margin-left:12px;">Back to list</a>
    </form>

    @push('scripts')
        <script>
            (function () {
                var sel = document.getElementById('CsUserBalanceChargetype');
                function sync() {
                    var show = sel && sel.value === 'installment';
                    document.querySelectorAll('.installment-row').forEach(function (el) {
                        el.style.display = show ? 'block' : 'none';
                    });
                }
                if (sel) {
                    sel.addEventListener('change', sync);
                    sync();
                }
            })();
        </script>
    @endpush
@endsection
