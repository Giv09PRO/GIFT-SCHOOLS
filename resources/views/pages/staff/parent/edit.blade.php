{{-- resources/views/pages/staff/parent/edit.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Edit Parent - ' . e($parent->first_name) . ' ' . e($parent->last_name))

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                {{-- Edit Form Card --}}
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Update Parent Details</h3>
                    </div>
                    {{-- Form Start --}}
                    {{-- Route model binding passes $parent automatically --}}
                    <form method="POST" action="{{ route('staff.manage.parents.update', $parent->id) }}"> {{-- Use raw ID for route generation --}}
                        @csrf
                        @method('PUT') {{-- Use PUT method for updates --}}

                        <div class="card-body">
                            {{-- Display Alerts --}}
                            @include('layouts.partials.alerts') {{-- Use the correct path --}}

                            <h5 class="mb-3 mt-2 text-primary">Parent Information</h5>
                            <div class="row">
                                {{-- Prefix --}}
                                <div class="col-md-2 form-group mb-3">
                                    <label for="name_prefix">Prefix</label>
                                    <input type="text" name="name_prefix" id="name_prefix" class="form-control @error('name_prefix') is-invalid @enderror" value="{{ old('name_prefix', $parent->name_prefix) }}">
                                    @error('name_prefix') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                {{-- First Name --}}
                                <div class="col-md-3 form-group mb-3">
                                    <label for="first_name">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $parent->first_name) }}" required>
                                    @error('first_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                {{-- Middle Name --}}
                                <div class="col-md-3 form-group mb-3">
                                    <label for="middle_name">Middle Name</label>
                                    <input type="text" name="middle_name" id="middle_name" class="form-control @error('middle_name') is-invalid @enderror" value="{{ old('middle_name', $parent->middle_name) }}">
                                    @error('middle_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                {{-- Last Name --}}
                                <div class="col-md-3 form-group mb-3">
                                    <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $parent->last_name) }}" required>
                                    @error('last_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                {{-- Suffix --}}
                                <div class="col-md-1 form-group mb-3">
                                    <label for="name_suffix">Suffix</label>
                                    <input type="text" name="name_suffix" id="name_suffix" class="form-control @error('name_suffix') is-invalid @enderror" value="{{ old('name_suffix', $parent->name_suffix) }}">
                                    @error('name_suffix') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div> {{-- /.row --}}

                            <div class="row">
                                {{-- Username --}}
                                <div class="col-md-4 form-group mb-3">
                                    <label for="username">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $parent->username) }}" required>
                                    @error('username') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                {{-- Gender --}}
                                <div class="col-md-4 form-group mb-3">
                                    <label for="gender">Gender</label>
                                    <select name="gender" id="gender" class="form-control @error('gender') is-invalid @enderror">
                                        <option value="" {{ old('gender', $parent->gender) == '' ? 'selected' : '' }}>Select Gender</option>
                                        <option value="Male" {{ old('gender', $parent->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ old('gender', $parent->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                                        {{-- Add other options if needed --}}
                                    </select>
                                    @error('gender') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div> {{-- /.row --}}

                            <div class="row">
                                {{-- Email --}}
                                <div class="col-md-6 form-group mb-3">
                                    <label for="email">Email Address</label>
                                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $parent->email) }}">
                                    @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                                {{-- Phone --}}
                                <div class="col-md-6 form-group mb-3">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $parent->phone) }}">
                                    @error('phone') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div> {{-- /.row --}}

                            <hr>
                            {{-- Link Students Section --}}
                            <h5 class="mb-3 mt-4 text-primary" id="link-students">Link Students</h5>
                            <div class="form-group">
                                <label for="student_ids">Select Students to Link:</label>
                                <select name="student_ids[]" id="student_ids"
                                        class="form-control select2-students @error('student_ids') is-invalid @enderror"
                                        multiple="multiple" data-placeholder="Select students...">
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}"
                                            {{ in_array($student->id, old('student_ids', $linkedStudentIds)) ? 'selected' : '' }}>
                                            {{ e($student->first_name) }} {{ e($student->last_name) }}
                                            ({{ e($student->prem_number ?: $student->username) }})
                                        </option>
                                    @endforeach
                                </select>

                                @error('student_ids')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                                @error('student_ids.*')
                                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            {{-- Field for relationship type --}}
                            <div class="form-group">
                                <label for="relationship">Relationship to Selected Student(s)</label>
                                <input type="text" name="relationship" id="relationship" class="form-control" value="{{ old('relationship', 'Parent/Guardian') }}">
                            </div>


                            <hr>
                            {{-- Optional Password Change --}}
                            <h5 class="mb-3 mt-4 text-primary">Change Password (Optional)</h5>
                            <p class="text-muted">Leave these fields blank if you do not want to change the password.</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">New Password</label>
                                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                                        @error('password')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password_confirmation">Confirm New Password</label>
                                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                        </div>
                        <div class="card-footer text-right">
                            <a href="{{ route('staff.manage.parents.show', $parent->id) }}" class="btn btn-default">Cancel</a>
                            <button type="submit" class="btn btn-info">Update Parent</button>
                        </div>
                    </form>
                    {{-- Form End --}}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Include scripts for select2 if not already in layout --}}
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script> {{-- Adjust path as needed --}}
    <script>
        $(function () {
            // Initialize Select2 Elements
            $('.select2').select2({ theme: 'bootstrap4' });
            $('.select2-students').select2({ theme: 'bootstrap4' });
        });
    </script>

    <script>
        $(document).ready(function () {
            $('.select2-students').select2({
                theme: 'bootstrap4',
                placeholder: 'Select students...',
                width: '100%',
                minimumInputLength: 1 // start searching after typing 1 character
            });
        });
    </script>
@endpush



@push('styles')
    {{-- Include styles for select2 if not already in layout --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}"> {{-- Adjust path as needed --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}"> {{-- Adjust path as needed --}}
@endpush
