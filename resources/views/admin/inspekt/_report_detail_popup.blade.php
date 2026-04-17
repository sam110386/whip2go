<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <form class="form-horizontal">
        <legend class="text-semibold">Vehicle Inspection Report</legend>
        <div class="form-group">
            <label class="col-lg-3 control-label text-bold">Upload Status :</label>
            <div class="col-lg-9">
                {{ $reportData['uploadStatus'] ?? 'N/A' }}
            </div>
        </div>
        @if ($reportFile !== null)
            <div class="form-group">
                <label class="col-lg-3 control-label text-bold">Report Url :</label>
                <div class="col-lg-9">
                    <a href="{{ $reportFile }}" target="_blank">Click Here</a>
                </div>
            </div>
        @endif
        <div class="form-group">
            <label class="col-lg-3 control-label text-bold">Vehicle Type :</label>
            <div class="col-lg-9">
                {{ $reportData['vehicleType'] ?? 'N/A' }}
            </div>
        </div>
        @if (!empty($reportData['geoLocation']))
            <div class="form-group">
                <label class="col-lg-3 control-label text-bold">Geo Location :</label>
                <div class="col-lg-9">
                    <a href="https://www.google.com/maps/search/{{ implode(',', $reportData['geoLocation']) }}?sa=X&ved=1t:242&ictx=111" target="_blank">{{ implode(',', $reportData['geoLocation']) }}</a>
                </div>
            </div>
        @endif
        @if (!empty($reportData['vehicleReadings']))
            <div class="form-group">
                <label class="col-lg-3 control-label text-bold">Vehicle Readings :</label>
                <div class="col-lg-9">
                    <ul>
                    @foreach ($reportData['vehicleReadings'] as $key => $val)
                        <li><span class="text-semibold">{{ $key }}:</span> {!! is_array($val) ? '<code>' . json_encode($val) . '</code>' : e($val) !!}</li>
                    @endforeach
                    </ul>
                </div>
            </div>
        @endif
        @if (!empty($reportData['fraudDetection']))
            <div class="form-group">
                <label class="col-lg-3 control-label text-bold">Fraud Detection :</label>
                <div class="col-lg-9">
                    <ul>
                    @foreach ($reportData['fraudDetection'] as $key => $val)
                        <li><span class="text-semibold">{{ $key }}:</span> {!! is_array($val) ? '<code>' . json_encode($val) . '</code>' : e($val) !!}</li>
                    @endforeach
                    </ul>
                </div>
            </div>
        @endif
        @if (!empty($reportData['preInspection']))
            <div class="form-group">
                <label class="col-lg-3 control-label text-bold">Pre Inspection :</label>
                <div class="col-lg-9">
                    <ul>
                    @foreach ($reportData['preInspection'] as $key => $val)
                        <li><span class="text-semibold">{{ $key }}:</span> {!! is_array($val) ? '<code>' . json_encode($val) . '</code>' : e($val) !!}</li>
                    @endforeach
                    </ul>
                </div>
            </div>
        @endif
        @if (!empty($reportData['relevantImages']))
            <div class="form-group">
                <label class="col-lg-3 control-label text-bold">Relevant Images :</label>
                <div class="col-lg-9">
                    @foreach ($reportData['relevantImages'] as $val)
                        <ul>
                            <li class="d-inline-flex">
                                <span class="text-semibold">{{ $val['imageTag'] }}:</span>
                                <div class="thumbnail origimgwrapper">
                                    <div class="thumb">
                                        <img src="{{ asset('img/placeholder.jpg') }}" alt="" class="media-preview">
                                        <div class="caption-overflow">
                                            <span>
                                                <a href="{{ $val['imageUrl'] }}" class="btn border-white text-white btn-flat btn-icon btn-rounded ml-5" target="_blank"><i class="icon-link2"></i></a>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="caption">
                                        <em class="no-margin">Original</em>
                                    </div>
                                </div>
                                <div class="thumbnail refimgwrapper">
                                    <div class="thumb">
                                        <img src="{{ asset('img/placeholder.jpg') }}" alt="" class="media-preview">
                                        <div class="caption-overflow">
                                            <span>
                                                <a href="{{ $val['originalImageURL'] }}" class="btn border-white text-white btn-flat btn-icon btn-rounded ml-5" target="_blank"><i class="icon-link2"></i></a>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="caption">
                                        <em class="no-margin">Processed</em>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    @endforeach
                </div>
            </div>
        @endif
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
</div>
