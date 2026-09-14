{{-- resources/views/pages/staff/grades/index.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Manage Classes')

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Classes List</h3>
                        <div class="card-tools">
                            {{-- Button to View Students Without Classs --}}
                            {{-- Assumes StudentPolicy and 'viewAny' permission check --}}
                            @can('viewAny', App\Models\Student::class)
                                <a href="{{ route('staff.grades.unassigned') }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-user-graduate mr-1"></i> View Unassigned Students
                                </a>
                            @endcan

                            {{-- Add New Class Level Button - Check Create Permission --}}
                            {{-- Assumes ClassLevelPolicy is registered --}}
                            @can('create', App\Models\GradeLevel::class)
                                <a href="{{ route('staff.grades.create') }}" class="btn btn-primary btn-sm ml-2"> {{-- Added margin --}}
                                    <i class="fas fa-plus mr-1"></i> Add New Class Level
                                </a>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- Display Success/Error Messages --}}
                        @include('layouts.partials.alerts') {{-- Use the correct path to your alerts partial --}}

                        {{-- AdminLTE Datatable --}}
                        {{-- $config['data'] contains formatted rows including action buttons from controller --}}
                        {{-- The controller now adds 'View Students' and 'Assign Students' buttons to the actionsHtml --}}
                        <x-adminlte-datatable id="gradesTable" :heads="$heads" :config="$config" striped hoverable responsive/>

                    </div>
                </div>
            </div>
        </div>
    </div>@endsection

{{-- Optional: Push specific scripts or styles if needed --}}
 @push('scripts')
<script>
    // Add custom JavaScript for this page if necessary
</script>
@endpush

 @push('styles')
<style>
    /* Add custom CSS for this page if necessary */
</style>
@endpush
