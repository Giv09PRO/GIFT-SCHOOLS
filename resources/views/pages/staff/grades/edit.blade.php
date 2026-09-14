{{-- resources/views/pages/staff/grades/edit.blade.php --}}

@extends('layouts.app')

{{-- Page Title (Browser Tab) --}}
@section('title', 'Edit Class Level - ' . e($grade->title))

{{-- Main Page Content --}}
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                {{-- Edit Form Card --}}
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Edit Class Level Details</h3>
                    </div>
                    {{-- Form Start --}}
                    {{-- Route model binding passes $grade object --}}
                    <form method="POST" action="{{ route('staff.grades.update', $grade) }}">
                        @csrf
                        @method('PUT') {{-- Use PUT method for updates --}}

                        <div class="card-body">
                            {{-- Display Alerts --}}
                            @include('layouts.partials.alerts') {{-- Use the correct path to your alerts partial --}}

                            <div class="row">
                                {{-- School (Dropdown, grouped by year) --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="school_id">School <span class="text-danger">*</span></label>
                                        <select name="school_id" id="school_id" class="form-control select2 @error('school_id') is-invalid @enderror" required>
                                            <option value="" disabled>Select School</option>
                                            @foreach($schoolsGrouped as $year => $schoolsInYear)
                                                <optgroup label="Year: {{ $year }}">
                                                    @foreach($schoolsInYear as $school)
                                                        <option value="{{ $school->id }}" {{ old('school_id', $grade->school_id) == $school->id ? 'selected' : '' }}>
                                                            {{ $school->title }} ({{ $year }})
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>

                                        @error('school_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Class Title --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="title">Class Title <span class="text-danger">*</span></label>
                                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $grade->title) }}" required>
                                        @error('title')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                            <div class="row">
                                {{-- Short Name --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="short_name">Short Name</label>
                                        <input type="text" name="short_name" id="short_name" class="form-control @error('short_name') is-invalid @enderror" value="{{ old('short_name', $grade->short_name) }}">
                                        @error('short_name')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Sort Order --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="sort_order">Sort Order</label>
                                        <input type="number" name="sort_order" id="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $grade->sort_order) }}" placeholder="Lower numbers appear first">
                                        @error('sort_order')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                            <div class="row">
                                {{-- Next Class Level --}}
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="next_grade_id">Next Class Level (for rollover)</label>
                                        <select name="next_grade_id" id="next_grade_id" class="form-control select2 @error('next_grade_id') is-invalid @enderror">
                                            <option value="">(None)</option>
                                            @foreach($gradeLevels as $nextGrade)
                                                <option value="{{ $nextGrade->id }}" {{ old('next_grade_id', $grade->next_grade_id) == $nextGrade->id ? 'selected' : '' }}>
                                                    {{ $nextGrade->title }} ({{ $nextGrade->school_syear }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('next_grade_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div> {{-- /.row --}}

                        </div>
                        <div class="card-footer text-right">
                            {{-- Use raw ID for cancel route --}}
                            <a href="{{ route('staff.grades.index') }}" class="btn btn-default">Cancel</a>
                            <button type="submit" class="btn btn-info">Update Class Level</button>
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
