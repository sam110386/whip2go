@extends('admin.layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr class="adminBoxHeading reportListingHeading">
            <td class="adminGridHeading heading">
                {{ 'Dashboard' }}
            </td>
            <td class="adminGridHeading">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="2" align="left"></td>
        </tr>
        <tr>
            <td align="left">
                <table>
                    <tr>
                        <td valign="bottom">
                            <img src="{{ legacy_asset('img/arrow.gif') }}" />
                        </td>
                        <td valign="top" class="heading-text" style="padding-top:10px;">
                            {{ 'Welcome to Admin Panel' }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" align="center">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection