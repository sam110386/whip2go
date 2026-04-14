@extends('admin.layouts.app')

@section('title', 'Checkr Status')

@section('header_title', 'Checkr Status')

@section('content')
    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">User Information</h5>
        </div>
        <div class="panel-body">
            <dl class="dl-horizontal">
                <dt>Name</dt>
                <dd>{{ e($user->first_name ?? '') }} {{ e($user->last_name ?? '') }}</dd>
                <dt>Email</dt>
                <dd>{{ e($user->email ?? '') }}</dd>
                <dt>Checkr Status</dt>
                <dd>{{ e($user->checkr_status ?? 'N/A') }}</dd>
            </dl>
        </div>
    </div>

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Checkr Reports</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Checkr ID</th>
                        <th>Report ID</th>
                        <th>MVR Report ID</th>
                        <th>Status</th>
                        <th>Report Data</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td>{{ $report->id }}</td>
                            <td>{{ e($report->checkr_id ?? '') }}</td>
                            <td>{{ e($report->report_id ?? '') }}</td>
                            <td>{{ e($report->motor_vehicle_report_id ?? '') }}</td>
                            <td>{{ e($report->status ?? '') }}</td>
                            <td>
                                @if(!empty($report->report))
                                    <a href="#" class="btn btn-xs btn-info view-report-btn" data-report-id="{{ $report->id }}">View</a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{ $report->created ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No reports found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:12px;">
        <a href="{{ $basePath }}/index" class="btn btn-default">Back to Users</a>
    </div>

    <div id="reportModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h5 class="modal-title">Report Data</h5>
                </div>
                <div class="modal-body">
                    <pre id="reportJsonDisplay" style="max-height:400px;overflow:auto;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function(){
    $('.view-report-btn').on('click', function(e){
        e.preventDefault();
        var reportId = $(this).data('report-id');
        $.ajax({
            url: '{{ $basePath }}/checkrreport',
            type: 'GET',
            data: { id: reportId },
            dataType: 'json',
            success: function(resp){
                if(resp.status){
                    $('#reportJsonDisplay').text(JSON.stringify(resp.report, null, 2));
                } else {
                    $('#reportJsonDisplay').text(resp.message || 'Unable to load report.');
                }
                $('#reportModal').modal('show');
            },
            error: function(){
                $('#reportJsonDisplay').text('Error loading report.');
                $('#reportModal').modal('show');
            }
        });
    });
});
</script>
@endpush
