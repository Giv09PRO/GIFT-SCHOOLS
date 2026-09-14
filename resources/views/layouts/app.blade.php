@extends('adminlte::page')

@section('title')
    {{ config('adminlte.title') }}
    @hasSection('subtitle')
        | @yield('subtitle')
    @endif
@stop

@section('plugins.Datatables', true)

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">@yield('title', 'Dashboard')</h1>
            </div>
            <div class="col-sm-6">
                @include('layouts.partials.breadcrumbs')
            </div>
        </div>
    </div>
@stop

@section('content')
    @php
        use App\Helpers\Qs;
    @endphp
    {{--  The main content of the page goes here --}}
    @yield('content_body')
@stop

@section('footer')
    <div class="float-right">
        Version: {{ config('app.version', '1.0.0') }}
    </div>
    <strong>
        <a href="{{ config('app.company_url', '#') }}">
            {{ config('app.company_name', 'School Management System') }}
        </a>
    </strong>
@stop

@section('css')
    <style type="text/css">
        .card {
            border-radius: 0.5rem;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            border-bottom: none;
        }

        .card-title {
            font-weight: 600;
        }
    </style>
    
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">


    @stack('styles')
@stop

@section('js')
    <script>
        // Any common JavaScript that needs to run on every page can go here.
        // $(document).ready(function() {
        //     console.log('AdminLTE jQuery script loaded'); // Debug (optional)
        // });
    </script>
    @stack('scripts')
@stop
