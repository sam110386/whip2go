@extends('admin.layouts.app')
@section('content')
    <script type="text/javascript">
        jQuery(document).ready(function () {

        });   
    </script>
    <!-- Modal -->
    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
            </div>
        </div>
    </div>
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Insurance</span> - Providers
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ url('/admin/insurance_providers/add') }}" class="btn btn-success">Add New</a>
            </div>
        </div>
    </div>
    <div class="row ">
        @if(session('flash_message'))
            <div class="alert alert-success">{{ session('flash_message') }}</div>
        @endif
        @if(session('flash_error'))
            <div class="alert alert-danger">{{ session('flash_error') }}</div>
        @endif
    </div>
    <div class="panel">
        <form action="{{ url('/admin/insurance_providers/index') }}" method="POST" class="form-horizontal">
            @csrf
            <div class="panel-body">
                <div class="col-md-2">
                    <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}"
                        placeholder="Keyword..">
                </div>
                <div class="col-md-2">
                    <button type="submit" name="search" value="search" class="btn btn-primary">SEARCH</button>
                </div>
            </div>
        </form>
    </div>

    <div class="panel">
        <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
            @include('admin.insurance_provider.elements.index')
        </div>
    </div>
@endsection