{{-- resources/views/pages/staff/staff/index.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Manage Staff')

    {{-- Main Page Content --}}
    @section('content')
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Staff List</h3>
                            <div class="card-tools">
                                {{-- Add New Staff Button - Check Create Permission --}}
                                @can('create', App\Models\Staff::class)
                                    <a href="{{ route('staff.manage.staff.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus mr-1"></i> Add New Staff
                                    </a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            {{-- Display Success/Error Messages --}}
                            @include('layouts.partials.flash_messages') {{-- Assuming you have a partial for alerts --}}

                            {{-- AdminLTE Datatable --}}
                            {{-- Note: $config['data'] already contains the formatted rows including action buttons --}}
                            {{-- The ID 'staffTable' can be used for custom JS if needed --}}
                            <x-adminlte-datatable id="staffTable" :heads="$heads" :config="$config" striped hoverable responsive/>

                        </div>
                    </div>
                </div>
            </div>
        </div>@endsection


     @push('scripts')
    <script>
        // Add custom JavaScript for this page if necessary
        // console.log('Staff index page loaded.');
    </script>
    @endpush

     @push('styles')
    <style>
        /* Add custom CSS for this page if necessary */
    </style>
    @endpush
