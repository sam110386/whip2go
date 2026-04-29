@extends('admin.layouts.app')
@section('content')
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("#frmadmin").validate();

            jQuery("#InsuranceProviderCountry").change(function () {
                if (jQuery(this).val() == 'US') {
                    jQuery("#usstate").removeClass('hide');
                    jQuery("#castate").addClass('hide');
                } else {
                    jQuery("#usstate").addClass('hide');
                    jQuery("#castate").removeClass('hide');
                }
            });
        });
    </script>

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold"></span> {{ $listTitle }}
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="row">
            <form action="{{ url('/admin/insurance_providers/add') }}" method="POST" enctype="multipart/form-data"
                id="frmadmin" class="form-horizontal">
                @csrf
                <div class="panel-body">
                    <div class="col-lg-6">

                        <div class="form-group">
                            <label class="col-lg-3 control-label">Name :<span class="text-danger">*</span></label>
                            <div class="col-lg-9">
                                <input type="text" name="InsuranceProvider[name]" class="form-control required"
                                    value="{{ old('InsuranceProvider.name', $record->name ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Address :</label>
                            <div class="col-lg-9">
                                <input type="text" name="InsuranceProvider[address]" class="form-control"
                                    value="{{ old('InsuranceProvider.address', $record->address ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">City :</label>
                            <div class="col-lg-9">
                                <input type="text" name="InsuranceProvider[city]" class="form-control"
                                    value="{{ old('InsuranceProvider.city', $record->city ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Country :</label>
                            <div class="col-lg-9">
                                <select name="InsuranceProvider[country]" id="InsuranceProviderCountry"
                                    class="form-control">
                                    <option value="US" {{ (old('InsuranceProvider.country', $record->country ?? '') == 'US' || empty(old('InsuranceProvider.country', $record->country ?? ''))) ? 'selected' : '' }}>United State</option>
                                    <option value="CA" {{ old('InsuranceProvider.country', $record->country ?? '') == 'CA' ? 'selected' : '' }}>Canada</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group {{ (old('InsuranceProvider.country', $record->country ?? '') == 'US' || empty(old('InsuranceProvider.country', $record->country ?? ''))) ? '' : 'hide' }}"
                            id="usstate">
                            <label class="col-lg-3 control-label">State :</label>
                            <div class="col-lg-9">
                                <select name="InsuranceProvider[state]" class="form-control">
                                    @foreach($usStates as $code => $name)
                                        <option value="{{ $code }}" {{ old('InsuranceProvider.state', $record->state ?? '') == $code ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group {{ old('InsuranceProvider.country', $record->country ?? '') != 'CA' ? 'hide' : '' }}"
                            id="castate">
                            <label class="col-lg-3 control-label">State :</label>
                            <div class="col-lg-9">
                                <select name="InsuranceProvider[castate]" class="form-control">
                                    @foreach($caStates as $code => $name)
                                        <option value="{{ $code }}" {{ old('InsuranceProvider.castate', $record->state ?? '') == $code ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label">Logo :</label>
                            <div class="col-lg-9">
                                <input type="file" name="InsuranceProvider[logo]" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Website Link :</label>
                            <div class="col-lg-9">
                                <input type="url" name="InsuranceProvider[link]" class="form-control"
                                    value="{{ old('InsuranceProvider.link', $record->link ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Status :</label>
                            <div class="col-lg-9">
                                <select name="InsuranceProvider[status]" class="form-control">
                                    <option value="1" {{ old('InsuranceProvider.status', $record->status ?? '') == '1' ? 'selected' : '' }}>Enable</option>
                                    <option value="0" {{ old('InsuranceProvider.status', $record->status ?? '') === '0' ? 'selected' : '' }}>Disable</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="col-lg-2 control-label">&nbsp;</label>
                            <div class="col-lg-6">
                                @if (empty($record->id))
                                    <button type="submit" class="btn btn-primary">Save</button>
                                @else
                                    <button type="submit" class="btn btn-primary">Update</button>
                                @endif
                                <button type="button" class="btn left-margin btn-cancel ml-5"
                                    onClick="goBack('/admin/insurance_providers/index')">Return</button>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="InsuranceProvider[id]" value="{{ $record->id ?? '' }}">
            </form>
        </div>
    </div>
@endsection