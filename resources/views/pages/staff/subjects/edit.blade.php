@extends('layouts.app') {{-- Assuming this is your main application layout --}}

@section('title', 'Edit Subject')
@section('subtitle', 'Modify: ' . ($subject->title ?? 'Subject'))

@section('content_body')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 mx-auto"> {{-- Centered column --}}
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Editing Subject: <strong>{{ $subject->title }}</strong></h3>
                    </div>
                    <form method="POST" action="{{ route('staff.subjects.update', $subject->subject_id) }}">
                        @csrf
                        @method('PUT') {{-- Method for updating resources --}}

                        <div class="card-body">
                            {{-- Display validation errors if any --}}
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="title">Subject Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" placeholder="Enter subject title" value="{{ old('title', $subject->title) }}" required>
                                @error('title')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="short_name">Short Name</label>
                                <input type="text" class="form-control @error('short_name') is-invalid @enderror" id="short_name" name="short_name" placeholder="e.g., MATH, ENG" value="{{ old('short_name', $subject->short_name) }}">
                                @error('short_name')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- School Selection --}}
                            {{-- A user who can 'edit subjects across all schools' can change the school.
                                 Otherwise, the school is typically fixed or reflects their current school.
                                 The $schoolsForDropdown variable from controller determines the options.
                            --}}
                            <div class="form-group">
                                <label for="school_id">School <span class="text-danger">*</span></label>
                                <select class="form-control @error('school_id') is-invalid @enderror" id="school_id" name="school_id"
                                        @if(isset($user) && !$user->can('edit subjects across all schools') && count($schoolsForDropdown ?? []) <= 1) disabled @endif required>
                                    @if(isset($user) && $user->can('edit subjects across all schools'))
                                        <option value="">Select School</option> {{-- Allow selection if admin --}}
                                    @endif
                                    @foreach($schoolsForDropdown ?? [] as $id => $name)
                                        <option value="{{ $id }}" {{ old('school_id', $subject->school_id) == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if(isset($user) && !$user->can('edit subjects across all schools') && count($schoolsForDropdown ?? []) <= 1)
                                    {{-- If disabled, include a hidden field to submit the current school_id --}}
                                    <input type="hidden" name="school_id" value="{{ $subject->school_id }}">
                                @endif
                                @error('school_id')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- Academic Year (SYEAR) Selection --}}
                            {{-- Similar logic: Super Admins might change it; for others, it might be fixed or limited. --}}
                            <div class="form-group">
                                <label for="syear">Academic Year (SYEAR) <span class="text-danger">*</span></label>
                                <select class="form-control @error('syear') is-invalid @enderror" id="syear" name="syear"
                                        @if(isset($user) && !$user->can('edit subjects across all schools')) disabled @endif required>
                                    {{-- Populate with $syearsForDropdown passed from controller --}}
                                    @foreach($syearsForDropdown ?? [] as $yearValue => $yearLabel)
                                        <option value="{{ $yearValue }}" {{ old('syear', $subject->syear) == $yearValue ? 'selected' : '' }}>
                                            {{ $yearLabel }}
                                        </option>
                                    @endforeach
                                </select>
                                @if(isset($user) && !$user->can('edit subjects across all schools'))
                                    {{-- If disabled, include a hidden field to submit the current syear --}}
                                    <input type="hidden" name="syear" value="{{ $subject->syear }}">
                                @endif
                                @error('syear')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <small class="form-text text-muted">The academic year this subject definition applies to.</small>
                            </div>

                            <div class="form-group">
                                <label for="sort_order">Sort Order</label>
                                <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" placeholder="Enter sort order (optional)" value="{{ old('sort_order', $subject->sort_order) }}">
                                @error('sort_order')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <small class="form-text text-muted">Optional numerical value to order subjects in lists.</small>
                            </div>

                            {{-- Add other fields if necessary, e.g., rollover_id --}}
                            @if(isset($user) && $user->can('manage subject rollover')) {{-- Example permission --}}
                            <div class="form-group">
                                <label for="rollover_id">Rollover ID (Advanced)</label>
                                <input type="number" class="form-control @error('rollover_id') is-invalid @enderror" id="rollover_id" name="rollover_id" placeholder="Enter rollover ID (optional)" value="{{ old('rollover_id', $subject->rollover_id) }}">
                                @error('rollover_id')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            @endif

                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Subject
                            </button>
                            <a href="{{ route('staff.subjects.index') }}" class="btn btn-outline-secondary float-right">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@push('styles')
    {{-- Add any specific styles for this page here if needed --}}
    <style>
        .card-primary.card-outline {
            border-top: 3px solid #007bff; /* AdminLTE primary color */
        }
    </style>
@endpush

@push('scripts')
    {{-- Add any page-specific JavaScript here if needed --}}
    <script>
        $(document).ready(function() {
            // Example: Initialize Select2 if you use it for dropdowns
            // $('.select2').select2();

            // Prevent multiple form submissions
            $('form').on('submit', function() {
                $(this).find('button[type="submit"]').prop('disabled', true).prepend('<i class="fas fa-spinner fa-spin"></i> ');
            });
        });
    </script>
@endpush
