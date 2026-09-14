{{-- resources/views/pages/staff/profile/index.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'My Profile')

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2"> {{-- Center the card --}}
                {{-- Profile Details Card --}}
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-id-card mr-1"></i>
                            Your Profile Information
                        </h3>
                        <div class="card-tools">
                            {{-- Edit Profile Button --}}
                            {{-- Assumes route 'staff.profile.edit' exists --}}
                            <a href="{{ route('staff.profile.edit') }}" class="btn btn-sm btn-info" title="Edit Profile">
                                <i class="fas fa-edit"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- Display Success/Error Messages --}}
                        @include('layouts.partials.alerts') {{-- Use the correct path --}}

                        <dl class="row">
                            <dt class="col-sm-4">Full Name</dt>
                            <dd class="col-sm-8">{{ e($staff->title) }} {{ e($staff->first_name) }} {{ e($staff->middle_name) }} {{ e($staff->last_name) }} {{ e($staff->name_suffix) }}</dd>

                            <dt class="col-sm-4">Username</dt>
                            <dd class="col-sm-8">{{ e($staff->username) }}</dd>

                            <dt class="col-sm-4">Email Address</dt>
                            <dd class="col-sm-8">{{ e($staff->email ?: 'N/A') }}</dd>

                            <dt class="col-sm-4">Your Role(s)</dt>
                            {{-- Use Spatie roles preferably, fall back to profile column --}}
                            <dd class="col-sm-8">
                                <span class="badge badge-success">{{ e($staff->getRoleNames()->implode(', ') ?: $staff->profile ?: 'N/A') }}</span>
                            </dd>

                            <dt class="col-sm-4">Assigned School</dt>
                            <dd class="col-sm-8">{{ e($staff->school->title ?? 'N/A') }}</dd>

                            <dt class="col-sm-4">Current School Year</dt>
                            <dd class="col-sm-8">{{ e($staff->syear ?: 'N/A') }}</dd>

                            <dt class="col-sm-4">Last Login</dt>
                            <dd class="col-sm-8">{{ $staff->last_login ? $staff->last_login->format('Y-m-d H:i:s') : 'N/A' }}</dd>

                            {{-- Add other relevant fields from your Staff model as needed --}}

                        </dl>
                    </div>
                    <div class="card-footer">
                        {{-- Optional: Add more info or links here --}}
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
