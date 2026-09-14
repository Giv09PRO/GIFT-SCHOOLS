{{-- resources/views/pages/staff/staff/edit.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Edit Staff - ' . e($staff->first_name) . ' ' . e($staff->last_name))

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                {{-- Edit Form Card --}}
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Edit Staff Details</h3>
                    </div>
                    {{-- Form Start --}}
                    {{-- Route model binding passes $staff automatically --}}
                    {{-- *** ADDED enctype for file uploads *** --}}
                    <form method="POST" action="{{ route('staff.manage.staff.update', $staff->getKey()) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT') {{-- Use PUT method for updates --}}

                        <div class="card-body">
                            {{-- Display Alerts --}}
                            @include('layouts.partials.alerts')

                            {{-- Personal Details Section --}}
                            <fieldset class="mb-3">
                                <legend class="text-sm text-info">Personal Information</legend>
                                <div class="row">
                                    {{-- First Name --}}
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

                                    {{-- Last Name --}}
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $staff->last_name) }}" required>
                                            @error('last_name')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div> {{-- /.row --}}

                                <div class="row">
                                    {{-- Title --}}
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="title">Title</label>
                                            <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $staff->title) }}">
                                            @error('title')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Suffix --}}
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="name_suffix">Suffix</label>
                                            <input type="text" name="name_suffix" id="name_suffix" class="form-control @error('name_suffix') is-invalid @enderror" value="{{ old('name_suffix', $staff->name_suffix) }}">
                                            @error('name_suffix')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Email --}}
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">Email Address</label>
                                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $staff->email) }}">
                                            @error('email')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div> {{-- /.row --}}
                            </fieldset>

                            {{-- Account & Role Section --}}
                            <fieldset class="mb-3">
                                <legend class="text-sm text-info">Account & Role</legend>
                                <div class="row">
                                    {{-- Username --}}
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="username">Username <span class="text-danger">*</span></label>
                                            <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $staff->username) }}" required>
                                            @error('username')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Role --}}
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="role">Assign Role <span class="text-danger">*</span></label>
                                            {{-- Check if user can manage roles before enabling the dropdown --}}
                                            @can('manageRoles', $staff)
                                                <select name="role" id="role" class="form-control select2 @error('role') is-invalid @enderror" required>
                                                    <option value="" disabled>Select Role</option>
                                                    @foreach($roles as $roleName => $roleLabel)
                                                        {{-- Use $assignedRole passed from controller --}}
                                                        <option value="{{ $roleName }}" {{ old('role', $assignedRole) == $roleName ? 'selected' : '' }}>
                                                            {{ $roleLabel }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('role')
                                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            @else
                                                {{-- If user cannot manage roles, show the current role as disabled text --}}
                                                <input type="text" class="form-control" value="{{ $assignedRole }}" disabled>
                                                <input type="hidden" name="role" value="{{ $assignedRole }}"> {{-- Still submit current role --}}
                                                <small class="form-text text-muted">You do not have permission to change this staff member's role.</small>
                                            @endcan
                                        </div>
                                    </div>
                                </div> {{-- /.row --}}

                                <div class="row">
                                    {{-- School --}}
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="current_school_id">Assign School</label>
                                            <select name="current_school_id" id="current_school_id" class="form-control select2 @error('current_school_id') is-invalid @enderror">
                                                <option value="">(None)</option>
                                                @foreach($schools as $id => $title)
                                                    <option value="{{ $id }}" {{ old('current_school_id', $staff->current_school_id) == $id ? 'selected' : '' }}>
                                                        {{ $title }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('current_school_id')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- School Year --}}
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="syear">School Year <span class="text-danger">*</span></label>
                                            {{-- Usually you wouldn't change the year assigned on edit, but allow if needed --}}
                                            <input type="number" name="syear" id="syear" class="form-control @error('syear') is-invalid @enderror" value="{{ old('syear', $staff->syear) }}" required placeholder="YYYY" min="1900" max="2100" step="1">
                                            @error('syear')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div> {{-- /.row --}}
                            </fieldset>

                            {{-- Photo Upload Section --}}
                            <fieldset class="mb-3">
                                <legend class="text-sm text-info">Profile Photo</legend>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="photo">Upload New Photo (Optional)</label>
                                            <div class="custom-file">
                                                {{-- Use custom-file-input for Bootstrap styling --}}
                                                <input type="file" name="photo" id="photo" class="custom-file-input @error('photo') is-invalid @enderror" accept="image/*">
                                                <label class="custom-file-label" for="photo">Choose file...</label>
                                                @error('photo')
                                                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span> {{-- Use d-block for feedback on custom-file --}}
                                                @enderror
                                                <small class="form-text text-muted">Max file size: 2MB. Allowed types: jpg, png, gif, webp.</small>
                                            </div>
                                        </div>

                                        {{-- Remove Photo Option --}}
                                        @if($staff->photo_path)
                                            <div class="form-check mt-2">
                                                <input type="checkbox" name="remove_photo" id="remove_photo" class="form-check-input" value="1" {{ old('remove_photo') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="remove_photo">Remove current photo</label>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-6 text-center">
                                        {{-- Display Current Photo --}}
                                        <label>Current Photo</label><br>
                                        <img src="{{ $staff->adminlte_image() }}" {{-- Use the model helper --}}
                                        alt="Current Photo"
                                             class="img-thumbnail"
                                             style="max-height: 150px; max-width: 150px;"> {{-- Adjust size as needed --}}
                                        @if(!$staff->photo_path)
                                            <p class="text-muted text-sm mt-1">(No photo uploaded)</p>
                                        @endif
                                    </div>
                                </div> {{-- /.row --}}
                            </fieldset>

                            {{-- *** REMOVED Password Change Section *** --}}
                            {{-- Password resets should be handled via the "Reset Password" action on the index page --}}

                        </div>
                        <div class="card-footer text-right">
                            {{-- Redirect back to index or show page --}}
                            <a href="{{ route('staff.manage.staff.index') }}" class="btn btn-default mr-2">Cancel</a>
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-save mr-1"></i> Update Staff Member
                            </button>
                        </div>
                    </form>
                    {{-- Form End --}}
                </div> {{-- /.card --}}
            </div> {{-- /.col --}}
        </div> {{-- /.row --}}
    </div> {{-- /.container-fluid --}}
@endsection

@push('scripts')
    {{-- Include scripts for select2 if not already in layout --}}
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script> {{-- Adjust path as needed --}}
    {{-- Script to show filename in Bootstrap custom file input --}}
    <script src="{{ asset('vendor/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script> {{-- Adjust path as needed --}}

    <script>
        $(function () {
            // Initialize Select2 Elements
            $('.select2').select2({
                theme: 'bootstrap4' // Optional: Use Bootstrap 4 theme
            });

            // Initialize bs-custom-file-input
            bsCustomFileInput.init();
        });
    </script>
@endpush

@push('styles')
    {{-- Include styles for select2 if not already in layout --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}"> {{-- Adjust path as needed --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}"> {{-- Adjust path as needed --}}
@endpush
