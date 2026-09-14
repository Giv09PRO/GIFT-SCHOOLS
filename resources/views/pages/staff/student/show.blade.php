
@extends('layouts.app') {{-- Use your main layout file (e.g., layouts.app or adminlte::page) --}}

@php
    use App\Helpers\Qs;
    // Variables passed from controller:
    // $student (with loaded enrollments->grade, enrollments->school)
    // $currentEnrollment (the specific enrollment record for display)
    // $activeFeesCount
    // $totalDue
    // $totalPaid
    // $overallBalance
    $currentYear = Qs::getCurrentSchoolYear();
@endphp

{{-- Page Title (Browser Tab) --}}
@section('title', 'Student Profile: ' . $student->first_name . ' ' . $student->last_name)

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">

        {{-- Display Session Flash Messages --}}
        @include('layouts.partials.flash_messages')

        <div class="row">
            {{-- Left Column - Student Details --}}
            <div class="col-md-6">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-graduate me-1"></i>
                            Student Information
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('staff.students.edit', $student->id) }}" class="btn btn-sm btn-info" title="Edit Student">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            {{-- Add other actions like Reset Password if needed --}}

                            <form action="{{ route('staff.students.reset_password', $student->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Reset password to default?');">
                               @csrf
                               @method('PUT') {{-- Or POST depending on route definition --}}
                            <button type="submit" class="btn btn-sm btn-warning" title="Reset Password"><i class="fas fa-key"></i> Reset Pass</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-4">Full Name</dt>
                            <dd class="col-sm-8">{{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }} {{ $student->name_suffix }}</dd>

                            <dt class="col-sm-4">Username</dt>
                            <dd class="col-sm-8">{{ $student->username }}</dd>

                            <dt class="col-sm-4">PReM Number</dt>
                            <dd class="col-sm-8">{{ $student->prem_number ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Gender</dt>
                            <dd class="col-sm-8">{{ $student->gender }}</dd>

                            <dt class="col-sm-4">Date of Birth</dt>
                            <dd class="col-sm-8">{{ $student->dob ? $student->dob->format('M d, Y') : 'N/A' }}</dd>

                            <dt class="col-sm-4">Email</dt>
                            <dd class="col-sm-8">{{ $student->email ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Phone</dt>
                            <dd class="col-sm-8">{{ $student->phone ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Address</dt>
                            <dd class="col-sm-8">{!! nl2br(e($student->address ?? 'N/A')) !!}</dd>

                            {{-- Display Custom Fields if they exist and have values --}}
                            @if($student->custom_200000004)
                                <dt class="col-sm-4">Family ID</dt> {{-- Example Label --}}
                                <dd class="col-sm-8">{{ $student->custom_200000004 }}</dd>
                            @endif
                            {{-- Add other custom fields similarly --}}

                        </dl>
                    </div>
                </div>

                {{-- Parent/Guardian Information --}}
                <div class="card card-info card-outline mt-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-friends me-1"></i>
                            Parent / Guardian Information
                        </h3>
                    </div>
                    <div class="card-body">
                        @if($student->parents->isNotEmpty())
                            @foreach($student->parents as $parent)
                                <dl class="row border-bottom pb-2 mb-3">
                                    <dt class="col-sm-4">Name</dt>
                                    <dd class="col-sm-8">{{ $parent->first_name }} {{ $parent->last_name }}</dd>

                                    <dt class="col-sm-4">Relation</dt>
                                    <dd class="col-sm-8">{{ $parent->pivot->relationship ?? 'Guardian' }}</dd>

                                    <dt class="col-sm-4">Phone</dt>
                                    <dd class="col-sm-8">{{ $parent->phone ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">Email</dt>
                                    <dd class="col-sm-8">{{ $parent->email ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">Address</dt>
                                    <dd class="col-sm-8">{!! nl2br(e($parent->address ?? 'N/A')) !!}</dd>
                                </dl>
                            @endforeach
                        @else
                            <div class="alert alert-warning mb-0">No parent or guardian information found.</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right Column - Enrollment & Financials --}}
            <div class="col-md-6">
                {{-- Current Enrollment Card --}}
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-school me-1"></i>
                            Current Enrollment ({{ $currentYear }})
                        </h3>
                    </div>
                    <div class="card-body">
                        @if($currentEnrollment)
                            <dl class="row">
                                <dt class="col-sm-4">Enrollment Status</dt>
                                <dd class="col-sm-8">
                                    @if($currentEnrollment->end_date === null)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                        (Ended: {{ $currentEnrollment->end_date->format('M d, Y') }})
                                    @endif
                                </dd>

                                <dt class="col-sm-4">School</dt>
                                <dd class="col-sm-8">{{ $currentEnrollment->school->title ?? 'N/A' }}</dd>

                                <dt class="col-sm-4">Grade Level</dt>
                                <dd class="col-sm-8">{{ $currentEnrollment->grade->title ?? 'N/A' }}</dd>

                                <dt class="col-sm-4">Start Date</dt>
                                <dd class="col-sm-8">{{ $currentEnrollment->start_date ? $currentEnrollment->start_date->format('M d, Y') : 'N/A' }}</dd>

                                <dt class="col-sm-4">Enrollment Code</dt>
                                <dd class="col-sm-8">{{ $currentEnrollment->enrollment_code ?? 'N/A' }}</dd>

                                @if($currentEnrollment->end_date)
                                    <dt class="col-sm-4">Drop Code</dt>
                                    <dd class="col-sm-8">{{ $currentEnrollment->drop_code ?? 'N/A' }}</dd>
                                @endif
                            </dl>
                        @else
                            <div class="alert alert-warning text-center">
                                No active enrollment found for the {{ $currentYear }} school year.
                            </div>
                        @endif
                    </div>
                </div>



                {{-- Financial Summary Card --}}
                @can('view finances')
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-dollar-sign me-1"></i>
                            Financial Summary ({{ $currentYear }})
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('staff.students.payments.index', $student->id) }}" class="btn btn-sm btn-primary" title="View Details">
                                <i class="fas fa-list-alt"></i> View Details
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-5">Active Fees Assigned</dt>
                            <dd class="col-sm-7">{{ $activeFeesCount ?? 'N/A' }}</dd> {{-- Uses value from controller --}}

                            <dt class="col-sm-5">Total Due</dt>
                            <dd class="col-sm-7">
                                {{ number_format($totalDue ?? 0, 2) }}
                            </dd>

                            <dt class="col-sm-5">Total Paid</dt>
                            <dd class="col-sm-7 text-success">
                                {{ number_format($totalPaid ?? 0, 2) }}
                            </dd>

                            <dt class="col-sm-5">Current Balance</dt>
                            <dd class="col-sm-7 fw-bold {{ ($overallBalance ?? 0) > 0.005 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($overallBalance ?? 0, 2) }}
                            </dd>
                        </dl>
                    </div>
                </div>
                @endcan

            </div>
        </div>
    </div>
@stop

{{-- Optional: Add page-specific CSS/JS if needed --}}
@section('css')
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script> console.log('Student show page loaded!'); </script>
@stop
