{{-- resources/views/pages/staff/profile/edit.blade.php --}}

@extends('layouts.app') {{-- This should point to your custom layout if it's not layouts.app --}}
{{-- Or potentially @extends('adminlte::page') if you don't need your custom app layout's overrides? --}}
{{-- Assuming layouts.app is the correct one based on the file you provided --}}

{{-- Set the Title for the Header and Browser Tab (based on your layout) --}}
@section('title', 'Edit My Profile')

{{-- Use content_body for the main page content, as yielded by your layout --}}
@section('content_body')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2"> {{-- Center the card --}}
                {{-- Edit Profile Form Card --}}
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Update Your Profile Information</h3>
                    </div>
                    {{-- Form Start --}}
                    <form method="POST" action="{{ route('staff.profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            {{-- Display Alerts --}}
                            {{-- Make sure this partial exists and works --}}
                            @include('layouts.partials.alerts')

                            <h5 class="mb-3 mt-2 text-primary">Personal Details</h5>
                            <div class="row">
                                {{-- First Name (Always editable) --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="first_name">First Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $staff->first_name) }}" required>
                                        @error('first_name')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Middle Name --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="middle_name">Middle Name</label>
                                        <input type="text" name="middle_name" id="middle_name" class="form-control @error('middle_name') is-invalid @enderror" value="{{ old('middle_name', $staff->middle_name) }}">
                                        @error('middle_name')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Last Name (Restricted) --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                        @can('can manage staff')
                                            <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $staff->last_name) }}" required>
                                        @else
                                            <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $staff->last_name) }}" readonly>
                                        @endcan
                                        @error('last_name')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                            <div class="row">
                                {{-- Title --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="title">Title</label>
                                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $staff->title) }}">
                                        @error('title')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Suffix --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name_suffix">Suffix</label>
                                        <input type="text" name="name_suffix" id="name_suffix" class="form-control @error('name_suffix') is-invalid @enderror" value="{{ old('name_suffix', $staff->name_suffix) }}">
                                        @error('name_suffix')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                            <hr>
                            <h5 class="mb-3 mt-4 text-primary">Account Details</h5>
                            <div class="row">
                                {{-- Username (Restricted) --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username">Username <span class="text-danger">*</span></label>
                                        @can('can manage staff')
                                            <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $staff->username) }}" required>
                                        @else
                                            <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $staff->username) }}" readonly>
                                        @endcan
                                        @error('username')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                {{-- Email (Readonly) --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $staff->email) }}" readonly>
                                        @error('email')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                        <small class="form-text text-muted">Email cannot be changed via this form.</small>
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                            <hr>
                            {{-- Optional Password Change --}}
                            <h5 class="mb-3 mt-4 text-primary">Change Password (Optional)</h5>
                            <p class="text-muted">Leave these fields blank if you do not want to change your password.</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="current_password">Current Password</label>
                                        <input type="password" name="current_password" id="current_password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password">
                                        @error('current_password')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                        <small class="form-text text-muted">Required only if changing password.</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <input type="password" name="new_password" id="new_password" class="form-control @error('new_password') is-invalid @enderror" autocomplete="new-password">
                                        @error('new_password')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="new_password_confirmation">Confirm New Password</label>
                                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control">
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                        </div>
                        <div class="card-footer text-right">
                            <a href="{{ route('staff.profile.show') }}" class="btn btn-default">Cancel</a>
                            <button type="submit" class="btn btn-info">Update Profile</button>
                        </div>
                    </form>
                    {{-- Form End --}}
                </div>
            </div>
        </div>
    </div>
@endsection {{-- End content_body section --}}

@push('scripts')
    {{-- Add JS if needed --}}
@endpush

@push('styles')
    {{-- Add custom CSS if needed --}}
    <style>
        input[readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
    </style>
@endpush
