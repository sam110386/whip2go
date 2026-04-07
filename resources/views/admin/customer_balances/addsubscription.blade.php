@extends('admin.layouts.app')

@section('title', $listTitle)

@section('content')
    @php
        $b = $balance;
        $ct = old('CsUserBalance.chargetype', $b !== null ? ($b->chargetype ?? 'subscription') : 'subscription');
    @endphp

    <h1>Dealer Charges</h1>

    @if (session('success'))
        <p style="color:#0a0;">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p style="color:#b00020;">{{ session('error') }}</p>
    @endif

    <form method="post"
          action="/admin/customer_balances/addsubscription/{{ $useridB64 }}{{ $b ? '/' . base64_encode((string)$b->id) : '' }}"
          id="frmadmin"
          style="max-width:820px;">

        @if ($b)
            <input type="hidden" name="CsUserBalance[id]" value="{{ $b->id }}">
        @endif

        <div style="margin-bottom:16px; font-size:14px;">
            <div><strong>Credit:</strong> {{ $b->credit ?? 0 }}</div>
            <div><strong>Debit:</strong> {{ $b->debit ?? 0 }}</div>
            <div><strong>Balance:</strong> {{ $b->balance ?? 0 }}</div>
        </div>

        <fieldset style="border:1px solid #ddd; padding:12px; margin-bottom:14px;">
            <legend style="text-align:center;">Charge Balance</legend>
            <div style="margin-bottom:10px;">
                <label>Type</label><br>
                <select name="CsUserBalance[type]" class="form-control" style="max-width:320px;">
                    @foreach ($balanceTypes as $k => $label)
                        <option value="{{ $k }}" @selected((int)old('CsUserBalance.type', $b->type ?? 9) === (int)$k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <label>Amount</label><br>
                <input type="text" name="CsUserBalance[balance]" value="{{ old('CsUserBalance.balance') }}"
                       class="form-control" style="max-width:200px;">
            </div>
        </fieldset>

        <fieldset style="border:1px solid #ddd; padding:12px; margin-bottom:14px;">
            <legend style="text-align:center;">Balance Capture Setting</legend>
            <div style="margin-bottom:10px;">
                <label>Capture as</label><br>
                <select name="CsUserBalance[chargetype]" id="CsUserBalanceChargetype" style="max-width:220px;">
                    <option value="subscription" @selected($ct === 'subscription')>Subscription</option>
                    <option value="lumpsum" @selected($ct === 'lumpsum')>Lumpsum</option>
                    <option value="installment" @selected($ct === 'installment')>Installment</option>
                </select>
            </div>
            <div class="installment-row" style="margin-bottom:10px; display:none;">
                <label>Installment type</label><br>
                <select name="CsUserBalance[installment_type]">
                    <option value="daily" @selected(old('CsUserBalance.installment_type', $b->installment_type ?? 'daily') === 'daily')>Daily</option>
                    <option value="weekly" @selected(old('CsUserBalance.installment_type', $b->installment_type ?? '') === 'weekly')>Weekly</option>
                </select>
            </div>
            <div class="subscription-weekday-row" style="margin-bottom:10px;">
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

        <button type="submit" style="padding:8px 16px;">Update</button>
        <a href="/admin/customer_balances/subscription/{{ $useridB64 }}" style="margin-left:12px;">Return</a>
    </form>

    @push('scripts')
        <script>
            (function () {
                var sel = document.getElementById('CsUserBalanceChargetype');
                function sync() {
                    if (!sel) return;
                    var v = sel.value;
                    var inst = document.querySelectorAll('.installment-row');
                    var subWd = document.querySelectorAll('.subscription-weekday-row');
                    if (v === 'installment') {
                        inst.forEach(function (el) { el.style.display = 'block'; });
                        subWd.forEach(function (el) { el.style.display = 'block'; });
                    } else if (v === 'subscription') {
                        inst.forEach(function (el) { el.style.display = 'none'; });
                        subWd.forEach(function (el) { el.style.display = 'none'; });
                    } else {
                        inst.forEach(function (el) { el.style.display = 'none'; });
                        subWd.forEach(function (el) { el.style.display = 'block'; });
                    }
                }
                if (sel) {
                    sel.addEventListener('change', sync);
                    sync();
                }
            })();
        </script>
    @endpush
@endsection
