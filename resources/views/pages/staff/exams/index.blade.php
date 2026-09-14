```blade
@extends('layouts.app')

@section('title', 'Exams')
@section('subtitle', 'Manage Exams')

@section('content_body')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Exam List</h3>
                <div class="card-tools">
                    @can('manage exams', App\Models\Exam::class)
                        <a href="{{ route('staff.exams.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Create Exam
                        </a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="school_id">School</label>
                        <select id="school_id" class="form-control">
                            @if($allSchoolsAccess)
                                <option value="" {{ !$schoolId ? 'selected' : '' }}>All Schools</option>
                            @endif
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}" {{ $school->id == $schoolId ? 'selected' : '' }}>
                                    {{ $school->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="syear">School Year</label>
                        <input type="number" id="syear" class="form-control" value="{{ $syear }}">
                    </div>
                </div>
                <table id="exams-table" class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Marking Period</th>
                        <th>Grade Level</th>
                        <th>Subjects</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
@endsection

@section('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function () {
            const table = $('#exams-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('staff.exams.index') }}',
                    data: function (d) {
                        const schoolId = $('#school_id').val();
                        if (schoolId) {
                            d.school_id = schoolId;
                        }
                        d.syear = $('#syear').val();
                    }
                },
                columns: [
                    { data: 'type', name: 'type' },
                    { data: 'description', name: 'description' },
                    { data: 'marking_period', name: 'marking_period' },
                    { data: 'gradelevel', name: 'gradelevel' },
                    { data: 'subjects', name: 'subjects' },
                    { data: 'exam_start_date', name: 'exam_start_date' },
                    { data: 'status', name: 'status' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                responsive: true,
                order: [[5, 'desc']], // Order by start date descending
                language: {
                    processing: '<i class="fa fa-spinner fa-spin"></i> Loading...'
                }
            });

            // Reload table when filters change
            $('#school_id, #syear').on('change', function () {
                table.draw();
            });
        });
    </script>
@endsection
```
