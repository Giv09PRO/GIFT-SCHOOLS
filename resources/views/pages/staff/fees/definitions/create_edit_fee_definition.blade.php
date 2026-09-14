@extends('layouts.app')

@php
    use App\Helpers\Qs;
    // Determine if we are editing or creating
    $isEditing = isset($feeDefinition) && $feeDefinition->id;
    $formAction = $isEditing ? route('staff.fees.definitions.update', $feeDefinition->id) : route('staff.fees.definitions.store');
    $formMethod = $isEditing ? 'PUT' : 'POST';
    $cardTitle = $isEditing ? 'Edit Fee Definition' : 'Create New Fee Definition';
    $buttonText = $isEditing ? 'Update Definition' : 'Save Definition';
@endphp

@section('title', $cardTitle)

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            @include('layouts.partials.alerts') {{-- For displaying success/error messages --}}

            <div class="card card-purple card-outline shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas {{ $isEditing ? 'fa-edit' : 'fa-plus-circle' }} me-2"></i>{{ $cardTitle }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('staff.fees.definitions.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list me-1"></i> View All Definitions
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ $formAction }}" id="feeDefinitionForm">
                        @csrf
                        @if($isEditing)
                            @method('PUT')
                        @endif

                        {{-- School Year --}}
                        <div class="form-group mb-3">
                            <label for="syear" class="form-label">School Year <span class="text-danger">*</span></label>
                            <select name="syear" id="syear" class="form-control select2 @error('syear') is-invalid @enderror" required>
                                @php
                                    $currentSelectedYearValue = old('syear', $isEditing ? $feeDefinition->syear : Qs::getCurrentSchoolYear());
                                    
                                    // Normalize $currentSelectedYearValue to a single integer year for selection logic
                                    $normalizedSelectedYearToSelect = null;
                                    if ($currentSelectedYearValue) {
                                        if (is_string($currentSelectedYearValue) && str_contains($currentSelectedYearValue, '-')) {
                                            $normalizedSelectedYearToSelect = (int)substr($currentSelectedYearValue, 0, 4); // Take starting year from "YYYY-YYYY"
                                        } elseif (is_numeric($currentSelectedYearValue)) {
                                            $normalizedSelectedYearToSelect = (int)$currentSelectedYearValue; // It's already a single year
                                        }
                                    }
                                    
                                    // Fallback if still null (e.g., Qs::getCurrentSchoolYear() returned null or non-parsable string)
                                    if ($normalizedSelectedYearToSelect === null) {
                                        $normalizedSelectedYearToSelect = (int)date('Y'); // Default to current PHP year
                                    }

                                    $currentPhpYear = (int)date('Y');
                                    // Define default loop boundaries relative to the current PHP execution year
                                    $defaultStartYearForLoop = $currentPhpYear - 5; 
                                    $defaultEndYearForLoop = $currentPhpYear + 2;   

                                    // Determine the actual loop boundaries, ensuring the selected year is included
                                    $startYearLoop = min($defaultStartYearForLoop, $normalizedSelectedYearToSelect);
                                    $endYearLoop = max($defaultEndYearForLoop, $normalizedSelectedYearToSelect);
                                @endphp
                                @for ($y = $startYearLoop; $y <= $endYearLoop; $y++)
                                    @php
                                        $optionValue = $y; // Value submitted will be the single year (e.g., 2025)
                                        $optionDisplay = $y; // Displayed text will also be the single year (e.g., 2025)
                                        $isSelected = ($normalizedSelectedYearToSelect == $y);
                                    @endphp
                                    <option value="{{ $optionValue }}" {{ $isSelected ? 'selected' : '' }}>
                                        {{ $optionDisplay }}
                                    </option>
                                @endfor
                            </select>
                            @error('syear')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">The academic year this fee definition applies to.</small>
                        </div>

                        {{-- Fee Name --}}
                        <div class="form-group mb-3"> 
                            <label for="fee_name" class="form-label">Fee Name / Title <span class="text-danger">*</span></label>
                            <input type="text" name="fee_name" id="fee_name" class="form-control @error('fee_name') is-invalid @enderror"
                                   value="{{ old('fee_name', $isEditing ? $feeDefinition->fee_name : '') }}" required
                                   placeholder="e.g., Standard Tuition Fees, Examination Fees, Bus Fees">
                            @error('fee_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Total Amount --}}
                        <div class="form-group mb-3">
                            <label for="total_amount" class="form-label">Total Amount ({{ Qs::getSetting('currency_symbol', '$') }}) <span class="text-danger">*</span></label>
                            <input type="number" name="total_amount" id="total_amount" class="form-control @error('total_amount') is-invalid @enderror"
                                   value="{{ old('total_amount', $isEditing ? $feeDefinition->total_amount : '') }}" required
                                   step="0.01" min="0" placeholder="e.g., 1500.00">
                            @error('total_amount')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">The total amount for this fee before it's divided into installments.</small>
                        </div>

                        {{-- Number of Installments --}}
                        <div class="form-group mb-3">
                            <label for="number_of_installments" class="form-label">Number of Installments <span class="text-danger">*</span></label>
                            <input type="number" name="number_of_installments" id="number_of_installments" class="form-control @error('number_of_installments') is-invalid @enderror"
                                   value="{{ old('number_of_installments', $isEditing ? $feeDefinition->number_of_installments : 1) }}" required
                                   min="1" placeholder="e.g., 1, 3, 4">
                            @error('number_of_installments')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">How many parts this fee will be divided into (e.g., 1 for a single payment, 3 for termly).</small>
                        </div>

                        {{-- Description (Optional) --}}
                        <div class="form-group mb-4">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                                      rows="3" placeholder="Provide a brief description or any notes about this fee structure.">{{ old('description', $isEditing ? $feeDefinition->description : '') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Submit Button --}}
                        <div class="text-center border-top pt-3 mt-3">
                            <button type="submit" class="btn btn-purple btn-lg px-4">
                                <i class="fas fa-save me-2"></i>{{ $buttonText }}
                            </button>
                            <a href="{{ route('staff.fees.definitions.index') }}" class="btn btn-secondary ms-2">
                                <i class="fas fa-times-circle me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    {{-- Select2 CSS (if not already globally available) --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    <style>
        .btn-purple {
            color: #fff;
            background-color: #6f42c1; /* Bootstrap purple */
            border-color: #6f42c1;
        }
        .btn-purple:hover {
            color: #fff;
            background-color: #5a32a3;
            border-color: #532f91;
        }
    </style>
@endpush

@push('scripts')
    {{-- jQuery and Select2 JS (if not already globally available) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            // Initialize Select2
            $('#syear').select2({
                theme: 'bootstrap4',
                placeholder: 'Select School Year',
                allowClear: false // Typically, a school year is required
            });

            // Optional: Add any other JS specific to this form
            console.log('Fee definition form JS loaded. Editing: {{ $isEditing ? "true" : "false" }}');
        });
    </script>
@endpush
