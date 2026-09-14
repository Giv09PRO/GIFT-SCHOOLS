{{-- resources/views/pages/staff/schools/show.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'School Details - ' . e($school->title))

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                {{-- School Details Card --}}
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-school mr-1"></i>
                            School Information
                        </h3>
                        <div class="card-tools">
                            {{-- Back Button --}}
                            <a href="{{ route('staff.schools.index') }}" class="btn btn-sm btn-default" title="Back to Schools List">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            {{-- Edit Button --}}
                            {{-- Assumes SchoolPolicy is registered and uses $school object --}}
                            @can('update', $school)
                                <a href="{{ route('staff.schools.edit', $school->id) }}" class="btn btn-sm btn-info" title="Edit School">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            @endcan
                            {{-- Delete Button --}}
                            @can('delete', $school)
                                {{-- Add safety check? Maybe check if school has relations before showing delete --}}
                                {{-- Example: @if(!$school->staff()->exists() && !$school->enrollments()->exists()) --}}
                                <form action="{{ route('staff.schools.destroy', $school->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this school? This might affect related records.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete School">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                                {{-- @endif --}}
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- Display Success/Error Messages --}}
                        @include('layouts.partials.alerts') {{-- Use the correct path to your alerts partial --}}

                        <dl class="row">
                            <dt class="col-sm-3">School Name</dt>
                            <dd class="col-sm-9">{{ e($school->title) }}</dd>

                            <dt class="col-sm-3">School Year</dt>
                            <dd class="col-sm-9">{{ e($school->syear) }}</dd>

                            <dt class="col-sm-3">School Number</dt>
                            <dd class="col-sm-9">{{ e($school->school_number ?: 'N/A') }}</dd>

                            <dt class="col-sm-3">Short Name</dt>
                            <dd class="col-sm-9">{{ e($school->short_name ?: 'N/A') }}</dd>

                            <dt class="col-sm-3">Address</dt>
                            <dd class="col-sm-9">{{ e($school->address ?: 'N/A') }}</dd>

                            <dt class="col-sm-3">City</dt>
                            <dd class="col-sm-9">{{ e($school->city ?: 'N/A') }}</dd>

                            <dt class="col-sm-3">State</dt>
                            <dd class="col-sm-9">{{ e($school->state ?: 'N/A') }}</dd>

                            <dt class="col-sm-3">Zip Code</dt>
                            <dd class="col-sm-9">{{ e($school->zipcode ?: 'N/A') }}</dd>

                            <dt class="col-sm-3">Phone</dt>
                            <dd class="col-sm-9">{{ e($school->phone ?: 'N/A') }}</dd>

                            <dt class="col-sm-3">Principal</dt>
                            <dd class="col-sm-9">{{ e($school->principal ?: 'N/A') }}</dd>

                            <dt class="col-sm-3">Website</dt>
                            <dd class="col-sm-9">
                                @if($school->www_address)
                                    <a href="{{ e($school->www_address) }}" target="_blank">{{ e($school->www_address) }}</a>
                                @else
                                    N/A
                                @endif
                            </dd>

                            <dt class="col-sm-3">Reporting GP Scale</dt>
                            <dd class="col-sm-9">{{ e($school->reporting_gp_scale ?: 'N/A') }}</dd>

                            <dt class="col-sm-3">Rotation Days</dt>
                            <dd class="col-sm-9">{{ e($school->number_days_rotation ?: 'N/A') }}</dd>

                            <dt class="col-sm-3">Created At</dt>
                            <dd class="col-sm-9">{{ $school->created_at ? $school->created_at->format('Y-m-d H:i:s') : 'N/A' }}</dd>

                            <dt class="col-sm-3">Last Updated At</dt>
                            <dd class="col-sm-9">{{ $school->updated_at ? $school->updated_at->format('Y-m-d H:i:s') : 'N/A' }}</dd>

                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>@endsection

@push('styles')
    <style>
        /* Add custom CSS if needed */
        .dl-row dt {
            font-weight: 600; /* Make definition list terms bolder */
        }
    </style>
@endpush
