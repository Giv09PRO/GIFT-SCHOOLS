{{-- resources/views/pages/staff/schools/create.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Add New School')

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                {{-- Create Form Card --}}
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Enter School Details</h3>
                    </div>
                    {{-- Form Start --}}
                    <form method="POST" action="{{ route('staff.schools.store') }}">
                        @csrf {{-- CSRF Protection --}}

                        <div class="card-body">
                            {{-- Display Alerts --}}
                            @include('layouts.partials.alerts') {{-- Use the correct path to your alerts partial --}}

                            <div class="row">
                                {{-- School Name (Title) --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="title">School Name <span class="text-danger">*</span></label>
                                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                                        @error('title')
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

                            <div class="row">
                                {{-- Address --}}
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <input type="text" name="address" id="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}">
                                        @error('address')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                            <div class="row">
                                {{-- City --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input type="text" name="city" id="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}">
                                        @error('city')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                {{-- State --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="state">State</label>
                                        <input type="text" name="state" id="state" class="form-control @error('state') is-invalid @enderror" value="{{ old('state') }}">
                                        @error('state')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                {{-- Zip Code --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="zipcode">Zip Code</label>
                                        <input type="text" name="zipcode" id="zipcode" class="form-control @error('zipcode') is-invalid @enderror" value="{{ old('zipcode') }}">
                                        @error('zipcode')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                            <div class="row">
                                {{-- Phone --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Phone</label>
                                        <input type="tel" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                                        @error('phone')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                {{-- Principal --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="principal">Principal</label>
                                        <input type="text" name="principal" id="principal" class="form-control @error('principal') is-invalid @enderror" value="{{ old('principal') }}">
                                        @error('principal')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                            <div class="row">
                                {{-- Website --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="www_address">Website Address</label>
                                        <input type="url" name="www_address" id="www_address" class="form-control @error('www_address') is-invalid @enderror" value="{{ old('www_address') }}" placeholder="http://www.example.com">
                                        @error('www_address')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                {{-- School Number --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="school_number">School Number</label>
                                        <input type="text" name="school_number" id="school_number" class="form-control @error('school_number') is-invalid @enderror" value="{{ old('school_number') }}">
                                        @error('school_number')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                            <div class="row">
                                {{-- Short Name --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="short_name">Short Name</label>
                                        <input type="text" name="short_name" id="short_name" class="form-control @error('short_name') is-invalid @enderror" value="{{ old('short_name') }}">
                                        @error('short_name')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                {{-- Reporting GP Scale --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="reporting_gp_scale">Reporting GP Scale</label>
                                        <input type="number" step="0.001" name="reporting_gp_scale" id="reporting_gp_scale" class="form-control @error('reporting_gp_scale') is-invalid @enderror" value="{{ old('reporting_gp_scale') }}">
                                        @error('reporting_gp_scale')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                {{-- Rotation Days --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="number_days_rotation">Rotation Days</label>
                                        <input type="number" step="1" min="0" name="number_days_rotation" id="number_days_rotation" class="form-control @error('number_days_rotation') is-invalid @enderror" value="{{ old('number_days_rotation') }}">
                                        @error('number_days_rotation')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                        </div>
                        <div class="card-footer text-right">
                            <a href="{{ route('staff.schools.index') }}" class="btn btn-default">Cancel</a>
                            <button type="submit" class="btn btn-success">Save School</button>
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
    {{-- <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script> --}} {{-- Adjust path as needed --}}
    <script>
        $(function () {
            // Initialize Select2 Elements if needed for future dropdowns
            // $('.select2').select2({
            //      theme: 'bootstrap4' // Optional: Use Bootstrap 4 theme
            // });
        });
    </script>
@endpush

@push('styles')
    {{-- Include styles for select2 if not already in layout --}}
    {{-- <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}"> --}} {{-- Adjust path as needed --}}
    {{-- <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}"> --}} {{-- Adjust path as needed --}}
@endpush
