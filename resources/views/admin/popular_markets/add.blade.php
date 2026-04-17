@extends('admin.layouts.app')

@section('title', ($listTitle ?? 'Add') . ' - Popular Market')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">{{ $listTitle ?? 'Add' }}</span> - Popular Market</h4>
            </div>
        </div>
    </div>
    <div class="row">
        @if(session('success'))
            <div class="col-md-12"><div class="alert alert-success">{{ session('success') }}</div></div>
        @endif
        @if(session('error'))
            <div class="col-md-12"><div class="alert alert-danger">{{ session('error') }}</div></div>
        @endif
    </div>
    <div class="panel">
        <div class="panel-body">
            <div class="row">
                @php
                    $actionUrl = $basePath . '/add' . (!empty($record->id) ? '/' . base64_encode((string)$record->id) : '');
                @endphp
                <form method="POST" action="{{ $actionUrl }}" name="PopularMarketForm" id="PopularMarketForm" class="form-horizontal">
                    @csrf
                    <div class="col-lg-12">
                        <div class="row form-group">
                            <label class="col-lg-2 control-label">Name : <span class="requiredField">*</span></label>
                            <div class="col-lg-7">
                                <input type="text" name="PopularMarket[name]" maxlength="100" class="form-control required"
                                       value="{{ old('PopularMarket.name', $record->name ?? '') }}">
                                @error('PopularMarket.name')
                                    <span class="help-block text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="row form-group">
                            <label class="col-lg-2 control-label">Location Latitude :<span class="requiredField">*</span></label>
                            <div class="col-lg-7">
                                <input type="text" name="PopularMarket[lat]" maxlength="20" class="form-control required"
                                       value="{{ old('PopularMarket.lat', $record->lat ?? '') }}">
                                @error('PopularMarket.lat')
                                    <span class="help-block text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Location Longitude :<span class="requiredField">*</span></label>
                            <div class="col-lg-7">
                                <input type="text" name="PopularMarket[lng]" maxlength="20" class="required form-control"
                                       value="{{ old('PopularMarket.lng', $record->lng ?? '') }}">
                                @error('PopularMarket.lng')
                                    <span class="help-block text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Radius (Miles) :<span class="requiredField">*</span></label>
                            <div class="col-lg-7">
                                <input type="text" name="PopularMarket[radius]" maxlength="6" class="required form-control"
                                       value="{{ old('PopularMarket.radius', $record->radius ?? '') }}">
                                @error('PopularMarket.radius')
                                    <span class="help-block text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Status:</label>
                            <div class="col-lg-7">
                                <select name="PopularMarket[status]" class="form-control">
                                    @php $st = (int) old('PopularMarket.status', $record->status ?? 1); @endphp
                                    <option value="1" @selected($st === 1)>Active</option>
                                    <option value="0" @selected($st === 0)>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="col-lg-2 control-label">&nbsp;</label>
                            <div class="col-lg-6">
                                @if(!empty($record->id))
                                    <button type="submit" class="btn btn-primary">Update</button>
                                @else
                                    <button type="submit" class="btn btn-primary">Save</button>
                                @endif
                                <button type="button" class="btn left-margin btn-cancel" onclick="goBack('{{ $basePath }}/index')">Cancel</button>
                            </div>
                        </div>
                    </div>
                    @if(!empty($record->id))
                        <input type="hidden" name="PopularMarket[id]" value="{{ (int) $record->id }}">
                    @endif
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $("#PopularMarketForm").validate();
        });
    </script>
@endpush
