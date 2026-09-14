{{-- resources/views/pages/staff/parent/show.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Parent Profile: ' . e($parent->first_name) . ' ' . e($parent->last_name))


{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            {{-- Left Column - Parent Info --}}
            <div class="col-md-6">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-auto mb-2 mb-md-0">
                                <h3 class="card-title m-0">
                                    <i class="fas fa-user-friends mr-1"></i>
                                    Parent Details
                                </h3>
                            </div>

                            <div class="col-12 col-md d-flex flex-wrap justify-content-md-end gap-1">
                                {{-- Back Button --}}
                                <a href="{{ route('staff.manage.parents.index') }}" class="btn btn-sm btn-default" title="Back to Parents List">
                                    <i class="fas fa-arrow-left"></i> Back
                                </a>

                                {{-- Edit Button --}}
                                @can('update', $parent)
                                    <a href="{{ route('staff.manage.parents.edit', $parent->id) }}" class="btn btn-sm btn-info" title="Edit Parent">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                @endcan

                                {{-- Reset Password Button --}}
                                @can('update', $parent) {{-- Or a specific 'resetPassword' permission if defined --}}
                                    <form action="{{ route('staff.manage.parents.reset_password', $parent->id) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to reset the password for this parent?');">
                                        @csrf
                                        @method('POST') {{-- Typically POST for actions like reset --}}
                                        <button type="submit" class="btn btn-sm btn-warning" title="Reset Password">
                                            <i class="fas fa-key"></i> Reset Password
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        {{-- Display Success/Error Messages --}}
                        @include('layouts.partials.alerts') {{-- Ensure this path is correct for your project --}}

                        <dl class="row">
                            <dt class="col-sm-4">Full Name</dt>
                            <dd class="col-sm-8">{{ collect([$parent->name_prefix, $parent->first_name, $parent->middle_name, $parent->last_name, $parent->name_suffix])->filter()->implode(' ') }}</dd>

                            <dt class="col-sm-4">Username</dt>
                            <dd class="col-sm-8">{{ e($parent->username) }}</dd>

                            <dt class="col-sm-4">Email Address</dt>
                            <dd class="col-sm-8">{{ e($parent->email ?: 'N/A') }}</dd>

                            <dt class="col-sm-4">Phone Number</dt>
                            <dd class="col-sm-8">{{ e($parent->phone ?: 'N/A') }}</dd>

                            <dt class="col-sm-4">Gender</dt>
                            <dd class="col-sm-8">{{ e($parent->gender ?: 'N/A') }}</dd>

                            <dt class="col-sm-4">Last Login</dt>
                            <dd class="col-sm-8">{{ $parent->last_login?->format(config('app.datetime_format', 'Y-m-d H:i:s')) ?? 'Never' }}</dd>

                            <dt class="col-sm-4">Account Created</dt>
                            <dd class="col-sm-8">{{ $parent->created_at?->format(config('app.datetime_format', 'Y-m-d H:i:s')) ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Last Updated</dt>
                            <dd class="col-sm-8">{{ $parent->updated_at?->format(config('app.datetime_format', 'Y-m-d H:i:s')) ?? 'N/A' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Right Column - Linked Students --}}
            <div class="col-md-6">
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-graduate mr-1"></i>
                            Linked Students
                        </h3>
                        <div class="card-tools">
                            {{-- Link to edit page where students can be managed --}}
                            @can('update', $parent)
                                <a href="{{ route('staff.manage.parents.edit', $parent->id) }}#link-students" {{-- Link to an anchor on edit page --}}
                                   class="btn btn-sm btn-outline-info" title="Manage Linked Students">
                                    <i class="fas fa-link"></i> Manage Links
                                </a>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body p-0"> {{-- Remove padding for table --}}
                        @if($parent->students->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Grade</th>
                                        <th>School</th>
                                        <th>Relationship</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($parent->students as $student)
                                        <tr>
                                            <td>{{ $student->id }}</td>
                                            <td>
                                                @can('view', $student) {{-- Check if user can view student profile --}}
                                                <a href="{{ route('staff.students.show', $student->id) }}">
                                                    {{ e($student->last_name) }}, {{ e($student->first_name) }}
                                                </a>
                                                @else
                                                    {{ e($student->last_name) }}, {{ e($student->first_name) }}
                                                @endcan
                                            </td>
                                            {{-- Accessing eager-loaded current active enrollment details --}}
                                            <td>{{ $student->currentActiveEnrollment?->grade?->title ?? 'N/A' }}</td>
                                            <td>{{ $student->currentActiveEnrollment?->school?->title ?? 'N/A' }}</td>
                                            <td>{{ e($student->pivot->relationship ?: 'N/A') }}</td> {{-- Display relationship from pivot --}}
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted p-3">No students are currently linked to this parent.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Add custom CSS if needed */
        .dl-row dt {
            font-weight: 600; /* Make definition list terms bolder */
        }
        .card-header .gap-1 { /* Ensure gap works with flex-wrap */
            gap: 0.25rem; /* Adjust as needed */
        }
    </style>
@endpush

@push('scripts')
    {{-- Add custom JS if needed --}}
@endpush
