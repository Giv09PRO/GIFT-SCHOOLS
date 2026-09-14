{{-- resources/views/pages/staff/staff/show.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Staff Profile - ' . e($staff->first_name) . ' ' . e($staff->last_name))

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            {{-- Left Column - Profile Info --}}
            <div class="col-md-8"> {{-- Adjusted column size --}}
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-tie mr-1"></i>
                            Staff Details
                        </h3>
                        <div class="card-tools">
                            {{-- Back Button --}}
                            <a href="{{ route('staff.manage.staff.index') }}" class="btn btn-sm btn-default" title="Back to Staff List">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            {{-- Edit Button --}}
                            @can('update', $staff)
                                <a href="{{ route('staff.manage.staff.edit', $staff->getKey()) }}" class="btn btn-sm btn-info" title="Edit Staff">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            @endcan
                            {{-- Reset Password Button --}}
                            @can('resetPassword', $staff)
                                <form action="{{ route('staff.manage.staff.reset_password', $staff->getKey()) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reset the password for {{ e($staff->first_name) }} {{ e($staff->last_name) }}?');">
                                    @csrf
                                    {{-- Route uses POST, method spoofing not needed --}}
                                    <button type="submit" class="btn btn-sm btn-warning" title="Reset Password">
                                        <i class="fas fa-key"></i> Reset Password
                                    </button>
                                </form>
                            @endcan
                            {{-- Delete Button --}}
                            @can('delete', $staff)
                                @if(Auth::user()->getKey() !== $staff->getKey()) {{-- Prevent self-delete via this button --}}
                                <form action="{{ route('staff.manage.staff.destroy', $staff->getKey()) }}" method="POST" class="d-inline" onsubmit="return confirm('WARNING: Deleting this staff member cannot be undone. Are you absolutely sure?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete Staff">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- Display Success/Error Messages --}}
                        @include('layouts.partials.alerts')

                        <dl class="row dl-row"> {{-- Added class for potential styling --}}
                            <dt class="col-sm-3">Full Name</dt>
                            <dd class="col-sm-9">{{ e($staff->getFullNameAttribute()) }}</dd>

                            <dt class="col-sm-3">Username</dt>
                            <dd class="col-sm-9">{{ e($staff->username) }}</dd>

                            <dt class="col-sm-3">Email Address</dt>
                            <dd class="col-sm-9">{{ e($staff->email ?: 'N/A') }}</dd>

                            <dt class="col-sm-3">Role(s)</dt>
                            {{-- Use Spatie roles preferably, fall back to profile column --}}
                            <dd class="col-sm-9">
                                <span class="badge badge-info">{{ e($staff->getRoleNames()->implode(', ') ?: $staff->profile ?: 'N/A') }}</span>
                            </dd>

                            <dt class="col-sm-3">Current School</dt>
                            <dd class="col-sm-9">{{ e($staff->school->title ?? 'N/A') }}</dd> {{-- Assumes school relationship is loaded or available --}}

                            <dt class="col-sm-3">School Year</dt> {{-- Clarified label --}}
                            <dd class="col-sm-9">{{ e($staff->syear ?: 'N/A') }}</dd>

                            <dt class="col-sm-3">Last Login</dt>
                            <dd class="col-sm-9">{{ $staff->last_login ? $staff->last_login->format('M d, Y H:i A') : 'Never' }} ({{ $staff->last_login ? $staff->last_login->diffForHumans() : '' }})</dd>

                            <dt class="col-sm-3">Member Since</dt>
                            <dd class="col-sm-9">{{ $staff->created_at ? $staff->created_at->format('M d, Y') : 'N/A' }}</dd>

                            <dt class="col-sm-3">Staff ID</dt>
                            <dd class="col-sm-9">{{ $staff->getKey() }}</dd>

                            {{-- Add other relevant fields from your Staff model as needed --}}
                            {{-- Example:
                            <dt class="col-sm-3">Custom Field</dt>
                            <dd class="col-sm-9">{{ e($staff->custom_200000001 ?: 'N/A') }}</dd>
                            --}}

                        </dl>
                    </div>
                </div>
            </div> {{-- /.col-md-8 --}}

            {{-- Right Column - Profile Picture & Quick Info --}}
            <div class="col-md-4"> {{-- Adjusted column size --}}
                {{-- Profile Picture Card --}}
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile text-center">
                        <div class="text-center mb-3">
                            {{-- *** ADDED PROFILE PICTURE *** --}}
                            <img class="profile-user-img img-fluid img-circle" {{-- Standard AdminLTE classes --}}
                            src="{{ $staff->adminlte_image() }}" {{-- Use model helper --}}
                                 alt="{{ e($staff->first_name) }}'s profile picture">
                        </div>

                        <h3 class="profile-username text-center">{{ e($staff->getFullNameAttribute()) }}</h3>

                        <p class="text-muted text-center">{{ e($staff->getRoleNames()->implode(', ') ?: $staff->profile ?: 'Staff Member') }}</p>

                        {{-- Example Quick Links/Stats (Optional) --}}
                        {{-- <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Status</b> <a class="float-right">Active</a>
                            </li>
                        </ul> --}}

                        {{-- Edit link again for convenience? --}}
                        {{-- @can('update', $staff)
                            <a href="{{ route('staff.manage.staff.edit', $staff->getKey()) }}" class="btn btn-primary btn-block"><b>Edit Profile</b></a>
                        @endcan --}}
                    </div>
                </div>
                {{-- Optional Second Card for additional info moved from left--}}
                {{-- <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle mr-1"></i>
                            Record Information
                        </h3>
                    </div>
                    <div class="card-body">
                        <strong><i class="fas fa-id-card mr-1"></i> Staff ID</strong>
                        <p class="text-muted">{{ $staff->getKey() }}</p>
                        <hr>

                        <strong><i class="fas fa-calendar-alt mr-1"></i> Member Since</strong>
                        <p class="text-muted">{{ $staff->created_at ? $staff->created_at->format('Y-m-d') : 'N/A' }}</p>
                        <hr>
                    </div>
                </div> --}}

            </div> {{-- /.col-md-4 --}}
        </div> {{-- /.row --}}
    </div> {{-- /.container-fluid --}}
@endsection

@push('styles')
    <style>
        /* Style definition list */
        .dl-row dt {
            font-weight: 600; /* Make definition list terms bolder */
            text-align: right;
            padding-right: 10px; /* Add some space between term and definition */
        }
        .dl-row dd {
            margin-bottom: .5rem; /* Spacing between rows */
        }
        /* Ensure profile image is nicely sized */
        .profile-user-img {
            width: 100px; /* Adjust as needed */
            height: 100px; /* Adjust as needed */
            object-fit: cover; /* Ensures the image covers the area without distortion */
        }
    </style>
@endpush

@push('scripts')
    {{-- Add any specific JS for this page if needed --}}
@endpush
