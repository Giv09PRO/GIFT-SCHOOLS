{{-- resources/views/pages/staff/staff/create.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Add New Staff Member')

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                {{-- Create Form Card --}}
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Enter Staff Details</h3>
                    </div>
                    {{-- Form Start --}}
                    <form method="POST" action="{{ route('staff.manage.staff.store') }}">
                        @csrf {{-- CSRF Protection --}}

                        <div class="card-body">
                            {{-- Display Alerts --}}
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {!! session('success') !!}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <div class="row">
                                {{-- First Name --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="first_name">First Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                                        @error('first_name')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Middle Name --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="middle_name">Middle Name</label>
                                        <input type="text" name="middle_name" id="middle_name" class="form-control @error('middle_name') is-invalid @enderror" value="{{ old('middle_name') }}">
                                        @error('middle_name')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Last Name --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required>
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
                                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}">
                                        @error('title')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Suffix --}}
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="name_suffix">Suffix</label>
                                        <input type="text" name="name_suffix" id="name_suffix" class="form-control @error('name_suffix') is-invalid @enderror" value="{{ old('name_suffix') }}">
                                        @error('name_suffix')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Username --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username">Username <span class="text-danger">*</span></label>
                                        <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" required>
                                        @error('username')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                            <div class="row">
                                {{-- Email --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                                        @error('email')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Role --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="role">Assign Role <span class="text-danger">*</span></label>
                                        <select name="role" id="role" class="form-control select2 @error('role') is-invalid @enderror" required>
                                            <option value="" disabled {{ old('role') ? '' : 'selected' }}>Select Role</option>
                                            @foreach($roles as $roleName => $roleLabel)
                                                <option value="{{ $roleName }}" {{ old('role') == $roleName ? 'selected' : '' }}>
                                                    {{ $roleLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('role')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                            <div class="row">
                                {{-- School --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="current_school_id">Assign School</label>
                                        <select name="current_school_id" id="current_school_id" class="form-control select2 @error('current_school_id') is-invalid @enderror">
                                            <option value="">(None / All Schools)</option> {{-- Option for admin/superadmin --}}
                                            @foreach($schools as $id => $title)
                                                <option value="{{ $id }}" {{ old('current_school_id') == $id ? 'selected' : '' }}>
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
                                        <input type="number" name="syear" id="syear" class="form-control @error('syear') is-invalid @enderror" value="{{ old('syear', date('Y')) }}" required placeholder="YYYY" min="1900" max="2100"> {{-- Default to current year --}}
                                        @error('syear')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                            <hr>
                            {{-- Password Info --}}
                            <p class="text-muted">A default password will be generated based on the user's last name using the system helper. The user should change this upon first login.</p>
                            {{-- Removed password input fields as per controller logic --}}

                        </div>
                        <div class="card-footer text-right">
                            <a href="{{ route('staff.manage.staff.index') }}" class="btn btn-default">Cancel</a>
                            <button type="submit" class="btn btn-success">Save Staff Member</button>
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
            $('.select2').select2({
                theme: 'bootstrap4' // Optional: Use Bootstrap 4 theme
            });
        });
    </script>
@endpush

@push('styles')
    {{-- Include styles for select2 if not already in layout --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}"> {{-- Adjust path as needed --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}"> {{-- Adjust path as needed --}}
@endpush
