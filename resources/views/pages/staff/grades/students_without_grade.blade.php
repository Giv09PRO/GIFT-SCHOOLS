{{-- resources/views/pages/staff/grades/students_without_grade.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Students Without Active Classes')

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Students Needing Class Assignment for {{ $currentYear }}</h3>
                        <div class="card-tools">
                            {{-- Simple Search Form --}}
                            <form method="GET" action="{{ route('staff.grades.unassigned') }}" class="form-inline float-right">
                                <div class="input-group input-group-sm" style="width: 250px;">
                                    <input type="text" name="student_search" class="form-control" placeholder="Search Name/ID/Username" value="{{ request('student_search') }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-default">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        {{-- Clear search button --}}
                                        @if(request()->has('student_search'))
                                            <a href="{{ route('staff.grades.unassigned') }}" class="btn btn-sm btn-warning ml-1" title="Clear Search">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        {{-- Display Alerts --}}
                        <div class="p-3"> {{-- Add padding for alerts inside card-body --}}
                            @include('layouts.partials.alerts')
                        </div>

                        @if($unassignedStudents->isEmpty())
                            <div class="alert alert-info m-3">
                                No students found currently needing class assignment for the {{ $currentYear }} school year.
                            </div>
                        @else
                            <table class="table table-hover table-striped">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Perm. Number</th>
                                    <th>Last Enrollment</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($unassignedStudents as $student)
                                    <tr>
                                        <td>{{ $student->id }}</td>
                                        <td>{{ e($student->last_name) }}, {{ e($student->first_name) }}</td>
                                        <td>{{ e($student->username) }}</td>
                                        <td>{{ e($student->prem_number ?: 'N/A') }}</td>
                                        <td>
                                            @php $lastEnrollment = $student->enrollments->sortByDesc('school_syear')->first(); @endphp
                                            @if($lastEnrollment)
                                                {{ $lastEnrollment->school_syear }}: {{ $lastEnrollment->grade->title ?? 'N/A' }}
                                                @if($lastEnrollment->end_date)
                                                    <span class="badge badge-secondary">Inactive</span>
                                                @endif
                                            @else
                                                No Prior Enrollment Found
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                {{-- Link to student profile --}}
                                                @can('view', $student)
                                                    <a href="{{ route('staff.students.show', $student->id) }}" class="btn btn-xs btn-primary" title="View Profile">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endcan
                                                {{-- Link to edit student --}}
                                                @can('update', $student)
                                                    <a href="{{ route('staff.students.edit', $student->id) }}" class="btn btn-xs btn-info" title="Edit Student">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                {{-- Delete Student Button --}}
                                                @can('delete', $student)
                                                    <form method="POST" action="{{ route('staff.students.destroy', $student->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete student {{ e($student->first_name) }} {{ e($student->last_name) }} ({{ e($student->username) }}) and all their related data? This action cannot be undone.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-xs btn-danger" title="Delete Student & Related Data">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                    @if($unassignedStudents->hasPages())
                        <div class="card-footer clearfix">
                            {{-- Display Pagination Links, appending search query if present --}}
                            {{ $unassignedStudents->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Add custom CSS if needed */
        .btn-group .btn {
            margin-right: 3px; /* Add a small margin between buttons in a group */
        }
        .btn-group form {
            margin-right: 3px; /* Consistent spacing for form button */
        }
    </style>
@endpush

{{-- No specific JavaScript needed in @push('scripts') for the delete confirmation as it's inline --}}
{{-- However, if you prefer a more modal-based confirmation, you'd add JS here --}}
