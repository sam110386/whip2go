@extends('layouts.admin')

@section('title', $title_for_layout ?? 'List / Unlist Rules')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Vehicles</span> - List / Unlist Rules</h4>
        </div>
        <div class="heading-elements">
            <a href="{{ $backUrl }}" class="btn btn-default">{{ $backLabel }}</a>
        </div>
    </div>
</div>
<div class="row">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
</div>
<div class="panel panel-flat">
    <div class="panel-heading">
        <h5 class="panel-title">Listing rules</h5>
        <p class="text-muted">Choose how vehicles are listed by default. You can then manually list or unlist individual vehicles from the Vehicles page.</p>
    </div>
    <div class="panel-body">
        <form action="{{ $formUrl }}" method="POST" id="ListUnlistRuleIndexForm">
            @csrf
            <div class="form-group">
                <label class="control-label">Rule</label>
                <div class="radio"><label>
                    <input type="radio" name="ListUnlistRule[listing_rule]" value="unlist_all" {{ $listingRule === 'unlist_all' ? 'checked' : '' }} />
                    Unlist all. I'll manually add vehicles
                </label></div>
                <div class="radio"><label>
                    <input type="radio" name="ListUnlistRule[listing_rule]" value="list_all" {{ $listingRule === 'list_all' ? 'checked' : '' }} />
                    List all. I'll manually unlist vehicles
                </label></div>
                <div class="radio"><label>
                    <input type="radio" name="ListUnlistRule[listing_rule]" value="unlist_by_ymm" {{ $listingRule === 'unlist_by_ymm' ? 'checked' : '' }} />
                    Unlist by year, make, model
                </label></div>
            </div>

            <div id="unlist-ymm-block" class="form-group" style="{{ ($listingRule !== 'unlist_by_ymm') ? 'display:none;' : '' }}">
                <label class="control-label">Unlist by year, make, model</label>
                <p class="help-block">Add rows for each combination you want unlisted. Leave a field blank to match any value.</p>
                <div id="unlist-rules-container">
                    @php
                        if (empty($unlistRules)) {
                            $unlistRules = [['year' => '', 'make' => '', 'model' => '']];
                        }
                    @endphp
                    @foreach($unlistRules as $i => $rule)
                        @php
                            $year  = isset($rule['year'])  ? e($rule['year'])  : '';
                            $make  = isset($rule['make'])  ? e($rule['make'])  : '';
                            $model = isset($rule['model']) ? e($rule['model']) : '';
                        @endphp
                        <div class="row unlist-rule-row mb-10">
                            <div class="col-md-2">
                                <input type="text" name="ListUnlistRule[unlist_rules][{{ $i }}][year]" class="form-control" placeholder="Year" value="{{ $year }}" />
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="ListUnlistRule[unlist_rules][{{ $i }}][make]" class="form-control" placeholder="Make" value="{{ $make }}" />
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="ListUnlistRule[unlist_rules][{{ $i }}][model]" class="form-control" placeholder="Model" value="{{ $model }}" />
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger btn-remove-rule">Remove</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" id="add-unlist-rule" class="btn btn-default">+ Add row</button>
            </div>

            <div class="form-group mt-20">
                <label class="checkbox-inline">
                    <input type="checkbox" name="ListUnlistRule[apply_now]" value="1" />
                    Apply rules to existing vehicles now
                </label>
                <p class="help-block">If checked, vehicle statuses will be updated according to the rule above when you save.</p>
            </div>

            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Save rules" />
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
(function() {
    var container = document.getElementById('unlist-rules-container');
    var block = document.getElementById('unlist-ymm-block');
    if (!container || !block) return;

    var radios = document.querySelectorAll('input[name="ListUnlistRule[listing_rule]"]');
    for (var i = 0; i < radios.length; i++) {
        radios[i].addEventListener('change', function() {
            block.style.display = (this.value === 'unlist_by_ymm') ? '' : 'none';
        });
    }

    function addRow(index) {
        var row = document.createElement('div');
        row.className = 'row unlist-rule-row mb-10';
        row.innerHTML = '<div class="col-md-2"><input type="text" name="ListUnlistRule[unlist_rules][' + index + '][year]" class="form-control" placeholder="Year" /></div>' +
            '<div class="col-md-3"><input type="text" name="ListUnlistRule[unlist_rules][' + index + '][make]" class="form-control" placeholder="Make" /></div>' +
            '<div class="col-md-3"><input type="text" name="ListUnlistRule[unlist_rules][' + index + '][model]" class="form-control" placeholder="Model" /></div>' +
            '<div class="col-md-2"><button type="button" class="btn btn-danger btn-remove-rule">Remove</button></div>';
        container.appendChild(row);
        row.querySelector('.btn-remove-rule').addEventListener('click', function() { row.remove(); });
    }

    document.getElementById('add-unlist-rule').addEventListener('click', function() {
        var rows = container.querySelectorAll('.unlist-rule-row');
        addRow(rows.length);
    });

    var removeButtons = container.querySelectorAll('.btn-remove-rule');
    for (var j = 0; j < removeButtons.length; j++) {
        removeButtons[j].addEventListener('click', function() {
            var r = this.closest('.unlist-rule-row');
            if (r && container.querySelectorAll('.unlist-rule-row').length > 1) r.remove();
        });
    }
})();
</script>
@endsection
