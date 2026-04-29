@extends('admin.layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="table-responsive">
        <table class="table table-bordered">
            <tr class="adminBoxHeading reportListingHeading">
                <td class="adminGridHeading heading">
                    {{ 'Dashboard' }}
                </td>
            </tr>
            <tr>
                <td>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <td>
                                    <img src="{{ asset('img/arrow.gif') }}" />
                                </td>
                                <td class="heading-text" style="padding-top:10px;">
                                    {{ 'Welcome to Admin Panel' }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>
@endsection