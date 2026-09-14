{{-- resources/views/pages/staff/schools/index.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Manage Schools')

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Schools List</h3>
                        <div class="card-tools">
                            {{-- Add New School Button - Check Create Permission --}}
                            {{-- Assumes SchoolPolicy is registered --}}
                            @can('create', App\Models\School::class)
                                <a href="{{ route('staff.schools.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus mr-1"></i> Add New School
                                </a>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- Display Success/Error Messages --}}
                        @include('layouts.partials.alerts') {{-- Use the correct path to your alerts partial --}}

                        {{-- AdminLTE Datatable --}}
                        {{-- $config['data'] contains formatted rows including action buttons from controller --}}
                        <x-adminlte-datatable id="schoolsTable" :heads="$heads" :config="$config" striped hoverable with-buttons/>

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
