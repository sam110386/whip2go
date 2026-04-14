@extends('layouts.main')

@section('title', $title_for_layout ?? 'Path To Ownership Setting')
@section('header_title', $title_for_layout ?? 'Path To Ownership Setting')

@section('content')
{{-- Stub: full port from `app/View/MsrpSettings/index.ctp` pending layout/forms migration. --}}
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-flat">
            <div class="panel-heading">
                <h6 class="panel-title">Path To Ownership down payment bands (MSRP)</h6>
            </div>
            <div class="panel-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <p class="text-muted">
                    Legacy form posts to <code>/msrp_settings/index</code> with <code>data[n][CsMsrpSetting][…]</code> fields.
                    Loaded rows: {{ count($msrpRequestData ?? []) }}.
                </p>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-flat">
            <div class="panel-heading">
                <h6 class="panel-title">Equity share</h6>
            </div>
            <div class="panel-body">
                <p class="text-muted">Equity save posts to <code>/msrp_settings/equaitysave</code> (share / other_vhshare).</p>
            </div>
        </div>
    </div>
</div>
@endsection
