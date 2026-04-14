@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
    <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr class="adminBoxHeading reportListingHeading">
            <td class="adminGridHeading heading">
                {{ 'Dashboard' }}
            </td>
        </tr>
        <tr>
            <td align="left">
                <table>
                    <tr>
                        <td valign="bottom">
                            <img src="{{ asset('img/arrow.gif') }}" border="0" />
                        </td>
                        <td valign="top" class="heading-text" style="padding-top:10px;">
                            {{ 'Welcome to Admin Panel' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection
