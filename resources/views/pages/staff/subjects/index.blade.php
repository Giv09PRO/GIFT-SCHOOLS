@extends('layouts.app') {{-- Assuming this is your main application layout --}}

@section('title', 'Manage Subjects')
@section('subtitle', 'List of all subjects')

{{-- If your layouts.app doesn't automatically load DataTables/SweetAlert2,
     you might need to push their CSS/JS links here or in dedicated sections.
     For example:
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush
--}}

@section('content_body')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">@yield('subtitle')</h3>
                        <div class="card-tools">
                            @can('create subjects') {{-- Permission check for creating subjects --}}
                            <a href="{{ route('staff.subjects.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Create New Subject
                            </a>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- Filters Section --}}
                        <div class="row mb-3 bg-light py-2 px-1 rounded border">
                            <div class="col-md-4 form-group">
                                <label for="school_filter" class="form-label">School:</label>
                                <select id="school_filter" name="school_filter" class="form-control form-control-sm"
                                        {{-- Disable school filter if user cannot view across all schools and only one school is available --}}
                                        @if(isset($user) && !$user->can('view subjects across all schools') && count($filterSchools ?? []) <= 1) disabled @endif >

                                    @if(isset($user) && $user->can('view subjects across all schools'))
                                        <option value="">All Schools</option>
                                    @endif

                                    @foreach($filterSchools ?? [] as $id => $name)
                                        <option value="{{ $id }}" @if(isset($selectedSchoolId) && $selectedSchoolId == $id) selected @endif>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="syear_filter" class="form-label">Academic Year:</label>
                                <select id="syear_filter" name="syear_filter" class="form-control form-control-sm">
                                    {{-- Option to select all years if applicable, or remove if a year must always be selected --}}
                                    {{-- <option value="">All Years</option> --}}
                                    @foreach($filterSyears ?? [] as $yearValue => $yearLabel)
                                        <option value="{{ $yearValue }}" @if(isset($selectedSyear) && $selectedSyear == $yearValue) selected @endif>
                                            {{ $yearLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end form-group">
                                <button id="apply_filters_btn" class="btn btn-info btn-sm w-100">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                            </div>
                        </div>

                        {{-- Subjects Table --}}
                        <div class="table-responsive">
                            <table id="subjectsTable" class="table table-bordered table-striped table-hover responsive nowrap" style="width:100%">
                                <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>Title</th>
                                    <th>Short Name</th>
                                    <th>School</th>
                                    <th>Sort Order</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                {{-- Data will be loaded by DataTables server-side processing --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@push('styles')
    {{-- Add any specific styles for this page here --}}
    <style>
        /* Ensure responsive DataTables icons are visible and styled appropriately */
        table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed>tbody>tr>th.dtr-control:before {
            background-color: #007bff; /* Primary color (e.g., AdminLTE blue) */
            border-color: white;
            color: white;
            box-shadow: 0 0 0 1px white, 0 0 0 2px #007bff; /* Create a nice border effect */
        }

        table.dataTable.dtr-inline.collapsed>tbody>tr.parent>td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed>tbody>tr.parent>th.dtr-control:before {
            background-color: #dc3545; /* Danger color for 'open' state (e.g., AdminLTE red) */
            border-color: white;
            color: white;
            box-shadow: 0 0 0 1px white, 0 0 0 2px #dc3545;
        }
        .form-label {
            font-weight: 500;
        }
    </style>
@endpush

@push('scripts')
    {{-- If not loaded globally by layouts.app, include DataTables and SweetAlert2 JS here --}}
    {{--
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    --}}

    <script>
        $(document).ready(function() {
            // Initialize DataTables
            const subjectsTable = $('#subjectsTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('staff.subjects.index') }}",
                    type: "GET",
                    data: function (d) {
                        // Pass filter values to the server
                        d.school_id_filter = $('#school_filter').val();
                        d.syear_filter = $('#syear_filter').val();
                    },
                    error: function (xhr, error, thrown) {
                        // Basic error handling for AJAX request
                        console.error("DataTables AJAX error: ", xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Failed to load data. Please try again or contact support.',
                        });
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'title', name: 'subjects.title' },
                    { data: 'short_name', name: 'subjects.short_name', defaultContent: '<em>N/A</em>' },
                    { data: 'school_name', name: 'schools.short_name' }, // Ensure this matches alias in controller
                    { data: 'sort_order', name: 'subjects.sort_order', className: 'text-center', defaultContent: '<em>N/A</em>' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                ],
                // Optional: Set default ordering, e.g., by title ascending
                // order: [[1, 'asc']],
                // Optional: Add buttons extension for export, print, etc.
                // dom: 'Bfrtip', // Example for buttons
                // buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                // language: {
                //     processing: '<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i><span class="sr-only">Loading...</span>',
                //     // Add other language customizations if needed
                // },
                // Defer rendering for performance on large datasets
                // deferRender: true,
            });

            // Apply Filters Button Click Handler
            $('#apply_filters_btn').on('click', function() {
                subjectsTable.ajax.reload(); // Reload DataTables with new filter values
            });

            // Handle Delete Button Click with SweetAlert2 Confirmation
            $('#subjectsTable').on('click', '.delete-subject-btn', function() {
                const subjectId = $(this).data('id');
                const deleteUrl = $(this).data('url');
                // Attempt to get a more descriptive name, fallback to ID
                let subjectTitle = $(this).closest('tr').find('td:nth-child(2)').text(); // Assumes title is in the 2nd column
                if (!subjectTitle || subjectTitle.trim() === 'N/A' || subjectTitle.trim() === '') {
                    subjectTitle = `Subject ID: ${subjectId}`;
                }


                Swal.fire({
                    title: 'Are you sure?',
                    html: `You are about to delete the subject: "<b>${subjectTitle}</b>".<br/>This action cannot be undone!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6', // Blue
                    cancelButtonColor: '#d33',   // Red
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: deleteUrl,
                            type: 'DELETE',
                            data: {
                                _token: "{{ csrf_token() }}", // CSRF token for security
                            },
                            success: function(response) {
                                Swal.fire(
                                    'Deleted!',
                                    response.success || 'Subject has been deleted.',
                                    'success'
                                );
                                subjectsTable.ajax.reload(null, false); // Reload DataTable, keep current page
                            },
                            error: function(xhr) {
                                let errorMessage = 'An error occurred while deleting the subject.';
                                if (xhr.responseJSON && xhr.responseJSON.error) {
                                    errorMessage = xhr.responseJSON.error;
                                } else if (xhr.statusText && xhr.statusText !== 'error') {
                                    errorMessage = xhr.statusText;
                                }
                                Swal.fire(
                                    'Error!',
                                    errorMessage,
                                    'error'
                                );
                            }
                        });
                    }
                });
            });

            // Display session flash messages using SweetAlert2
            @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ session('success') }}',
                timer: 3000, // Auto-close after 3 seconds
                showConfirmButton: false
            });
            @endif

            @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '{{ session('error') }}',
                // timer: 5000, // Optionally auto-close errors too
                showConfirmButton: true // Keep confirm button for errors
            });
            @endif

            @if(session('info'))
            Swal.fire({
                icon: 'info',
                title: 'Info',
                text: '{{ session('info') }}',
                timer: 4000,
                showConfirmButton: false
            });
            @endif

        });
    </script>
@endpush
