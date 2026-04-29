@extends('admin.layouts.app')

@section('title', $listTitle ?? 'View Email Template')

@section('content')
@php
    $et = $emailTemplate ?? [];
    $pageTitle = trim((string) ($et['title'] ?? ''));
    if ($pageTitle === '') {
        $pageTitle = (string) ($et['head_title'] ?? '');
    }
@endphp
<div class="panel">
    <section class="right_content">
        <table class="table">
            <tr>
                <td>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <td>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <tr class="adminBoxHeading reportListingHeading">
                                                <td class="adminGridHeading heading"><h3 style="margin:10px 0;">{{ $listTitle ?? 'View Email Template' }}</h3></td>
                                                <td class="adminGridHeading text-right">
                                                    <a href="/admin/email_templates/index" class="btn btn-default btn-sm">Back</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">
                                                    <table class="adminBox table table-bordered">
                                                        <tr>
                                                            <td width="18%" class="text-right">Page Title</td>
                                                            <td width="2%" class="text-center">:</td>
                                                            <td>{{ e($pageTitle) }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-right">Subject</td>
                                                            <td class="text-center">:</td>
                                                            <td>{{ e($et['subject'] ?? '') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-right">Content</td>
                                                            <td class="text-center">:</td>
                                                            <td>{!! $et['description'] ?? '' !!}</td>
                                                        </tr>
                                                        @if(!empty($et['created']))
                                                            <tr>
                                                                <td class="text-right">Created on</td>
                                                                <td class="text-center">:</td>
                                                                <td>
                                                                    @php $c = $et['created']; @endphp
                                                                    @if($c && (string) $c !== '0000-00-00 00:00:00')
                                                                        {{ \Carbon\Carbon::parse($c)->format('m/d/Y h:i A') }}
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endif
                                                        @if(!empty($et['modified']))
                                                            <tr>
                                                                <td class="text-right">Modified on</td>
                                                                <td class="text-center">:</td>
                                                                <td>
                                                                    @php $m = $et['modified']; @endphp
                                                                    @if($m && (string) $m !== '0000-00-00 00:00:00')
                                                                        {{ \Carbon\Carbon::parse($m)->format('m/d/Y h:i A') }}
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </section>
</div>
@endsection
