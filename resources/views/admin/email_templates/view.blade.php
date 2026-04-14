@extends('layouts.admin')

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
        <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
            <tr>
                <td valign="top">
                    <table align="center" width="98%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0">
                                    <tr class="adminBoxHeading reportListingHeading">
                                        <td class="adminGridHeading heading"><h3 style="margin:10px 0;">{{ $listTitle ?? 'View Email Template' }}</h3></td>
                                        <td class="adminGridHeading" align="right">
                                            <a href="/admin/email_templates/index" class="btn btn-default btn-sm">Back</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <table class="adminBox table table-bordered" border="0" cellpadding="8" cellspacing="0" width="100%">
                                                <tr>
                                                    <td width="18%" align="right" valign="top">Page Title</td>
                                                    <td width="2%" align="center" valign="top">:</td>
                                                    <td align="left">{{ e($pageTitle) }}</td>
                                                </tr>
                                                <tr>
                                                    <td align="right" valign="top">Subject</td>
                                                    <td align="center" valign="top">:</td>
                                                    <td align="left">{{ e($et['subject'] ?? '') }}</td>
                                                </tr>
                                                <tr>
                                                    <td align="right" valign="top">Content</td>
                                                    <td align="center" valign="top">:</td>
                                                    <td align="left">{!! $et['description'] ?? '' !!}</td>
                                                </tr>
                                                @if(!empty($et['created']))
                                                    <tr>
                                                        <td align="right" valign="top">Created on</td>
                                                        <td align="center" valign="top">:</td>
                                                        <td align="left">
                                                            @php $c = $et['created']; @endphp
                                                            @if($c && (string) $c !== '0000-00-00 00:00:00')
                                                                {{ \Carbon\Carbon::parse($c)->format('m/d/Y h:i A') }}
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endif
                                                @if(!empty($et['modified']))
                                                    <tr>
                                                        <td align="right" valign="top">Modified on</td>
                                                        <td align="center" valign="top">:</td>
                                                        <td align="left">
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
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </section>
</div>
@endsection
